<?php

namespace App\Http\Controllers\Transaction;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Discount\Services\DiscountService;
use App\Domain\Notification\Services\EmailService;
use App\Domain\Transaction\Services\TransactionService;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Schedule;
use App\Models\Transaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class TransactionController extends Controller
{
    public function __construct(private readonly TransactionService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Transaction::class);

        $query = Transaction::with(['customer', 'cashier', 'branch'])
            ->orderByDesc('transaction_date');

        /** @var User|null $user */
        $user = auth()->user();
        if ($user && ! $user->hasRole('owner')) {
            $query->whereIn('branch_id', $user->branches()->pluck('branches.id'));
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($branchId = $request->input('branch_id')) {
            $query->where('branch_id', $branchId);
        }

        return view('transactions.index', [
            'transactions' => $query->paginate(20)->withQueryString(),
            'branches' => Branch::active()->orderBy('name')->get(),
            'statuses' => Transaction::STATUSES,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Transaction::class);

        /** @var User|null $user */
        $user = auth()->user();
        $branchIds = $user && ! $user->hasRole('owner')
            ? $user->branches()->pluck('branches.id')->all()
            : null;

        $products = Product::active()
            ->with('category')
            ->when($branchIds !== null, fn ($q) => $q->whereIn('branch_id', $branchIds))
            ->orderBy('name')
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'price' => (float) $p->base_price,
                'unit' => $p->unit,
                'category' => $p->category?->name ?? '-',
                'category_id' => $p->category_id,
                'stockable' => in_array($p->category?->type_slug, config('transactions.stockable_category_slugs'), true),
                'stock' => (int) $p->stock_quantity,
            ]);

        $quickProducts = $products
            ->mapWithKeys(fn (array $p) => [
                $p['id'] => $p['name'].' — Rp '.number_format($p['price'], 0, ',', '.'),
            ])
            ->all();

        return view('transactions.create', [
            'products' => $products,
            'quickProducts' => $quickProducts,
            'categories' => ProductCategory::orderBy('sort_order')->get(),
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'schedules' => Schedule::query()
                ->whereIn('status', ['draft', 'confirmed'])
                ->orderBy('date_start')
                ->get(['id', 'product_id', 'date_start'])
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'label' => $s->product?->name.' — '.$s->date_start->translatedFormat('d M H:i'),
                ]),
            'paymentMethods' => Payment::METHODS,
            'ppnRate' => config('transactions.ppn.rate'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Transaction::class);

        // POS mengirim keranjang sebagai JSON; API/test boleh kirim array langsung.
        if ($request->filled('items_json')) {
            $request->merge(['items' => json_decode($request->input('items_json'), true)]);
        }
        if ($request->filled('payments_json')) {
            $request->merge(['payments' => json_decode($request->input('payments_json'), true)]);
        }

        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'discount_code' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.schedule_id' => ['nullable', 'integer', 'exists:schedules,id'],
            'items.*.equipment_unit_id' => ['nullable', 'integer', 'exists:equipment_units,id'],
            'payments' => ['nullable', 'array'],
            'payments.*.method' => ['required_with:payments', Rule::in(Payment::METHODS)],
            'payments.*.amount' => ['required_with:payments', 'numeric', 'min:0.01'],
            'payments.*.reference_no' => ['nullable', 'string', 'max:64'],
        ]);

        try {
            $transaction = $this->service->create($data, $data['items'], $data['payments'] ?? []);
        } catch (RuntimeException|InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', __('ui.transaction_created'));
    }

    public function show(Transaction $transaction): View
    {
        $this->authorize('view', $transaction);

        $transaction->load(['items.product.category', 'payments', 'customer', 'cashier', 'branch']);

        return view('transactions.show', [
            'transaction' => $transaction,
            'remaining' => $this->service->remaining($transaction),
            'paymentMethods' => Payment::METHODS,
        ]);
    }

    /**
     * E-receipt / invoice versi PDF (stream inline, bisa di-save-as).
     */
    public function pdf(Transaction $transaction): Response
    {
        $this->authorize('view', $transaction);

        $transaction->load(['items.product.category', 'payments', 'customer', 'cashier', 'branch']);

        $pdf = PdfFacade::loadView('pdf.receipt', [
            'transaction' => $transaction,
            'remaining' => $this->service->remaining($transaction),
        ])->setPaper('a5');

        return $pdf->stream("receipt-{$transaction->id}.pdf");
    }

    /**
     * Daftar tagihan (transaksi confirmed/partial yang belum lunas).
     */
    public function invoices(Request $request): View
    {
        $this->authorize('viewAny', Transaction::class);

        $query = Transaction::query()
            ->whereIn('status', ['confirmed', 'partial'])
            ->with(['customer', 'branch'])
            ->orderByDesc('transaction_date');

        if (auth()->user()->hasRole('admin-cabang')) {
            $branchIds = auth()->user()->branches()->pluck('branches.id');
            $query->whereIn('branch_id', $branchIds);
        } elseif ($branchId = $request->input('branch_id')) {
            $query->where('branch_id', $branchId);
        }

        return view('transactions.invoices', [
            'invoices' => $query->paginate(20)->withQueryString(),
            'totalOutstanding' => (clone $query)->get()->sum(fn ($t) => max(0, bcsub((string) $t->grand_total, $t->paidTotal(), 2))),
        ]);
    }

    /**
     * Terbitkan tagihan untuk booking (transaksi tanpa pembayaran).
     */
    public function issueInvoice(Booking $booking, Request $request): RedirectResponse
    {
        $this->authorize('pay', $booking);

        if ($booking->transaction_id) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', __('ui.invoice_already_issued'));
        }

        /** @var User $actor */
        $actor = $request->user();
        $previous = auth()->user();
        auth()->login($actor);

        try {
            $transaction = $this->service->create(
                [
                    'branch_id' => $booking->branch_id,
                    'customer_id' => $booking->customer_id,
                    'idempotency_key' => 'booking-'.$booking->id,
                ],
                [[
                    'product_id' => $booking->unit->product_id,
                    'qty' => $booking->nights(),
                ]],
                []
            );

            $transaction->forceFill([
                'transaction_date' => $booking->date_start->copy()->setTime(12, 0),
            ])->save();

            $booking->forceFill(['transaction_id' => $transaction->id])->save();
        } finally {
            if ($previous) {
                auth()->setUser($previous);
            }
        }

        AuditLogger::log('invoice_issued', $booking, null, [
            'transaction_id' => $transaction->id,
            'amount' => $transaction->grand_total,
        ]);

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', __('ui.invoice_issued'));
    }

    /**
     * Kirim email tagihan (PDF terlampir otomatis oleh job).
     */
    public function sendInvoiceEmail(Transaction $transaction, Request $request): RedirectResponse
    {
        $this->authorize('view', $transaction);

        $customer = $transaction->customer;

        if (! $customer || ! $customer->email) {
            return redirect()
                ->route('transactions.show', $transaction)
                ->with('error', __('ui.invoice_no_customer_email'));
        }

        app(EmailService::class)->queueInvoice(
            $customer,
            $transaction,
            subject: __('messages.email_invoice_subject', ['no' => $transaction->id]),
            invoiceData: [
                'transaction_no' => $transaction->id,
                'date' => $transaction->transaction_date?->format('d M Y'),
                'grand_total' => (float) $transaction->grand_total,
                'paid_total' => $transaction->paidTotal(),
                'customer_name' => $customer->name,
            ],
        );

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', __('ui.invoice_email_queued'));
    }

    public function discountPreview(Request $request, DiscountService $discountService): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
        ]);

        $lines = [];
        foreach ($data['items'] as $item) {
            $product = Product::with('category')->findOrFail($item['product_id']);
            $lines[] = [
                'type_slug' => $product->category?->type_slug,
                'line_total' => bcmul((string) $product->base_price, (string) max(1, (int) $item['qty']), 2),
            ];
        }

        try {
            $result = $discountService->resolveAndCalculate(
                $data['code'],
                $lines,
                $data['customer_id'] ?? null,
                (int) $data['branch_id'],
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['valid' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'valid' => true,
            'amount' => $result['amount'],
            'formatted' => 'Rp '.number_format((float) $result['amount'], 0, ',', '.'),
            'name' => $result['discount']->name,
        ]);
    }

    public function addPayment(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorize('pay', $transaction);

        $data = $request->validate([
            'method' => ['required', Rule::in(Payment::METHODS)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference_no' => ['nullable', 'string', 'max:64'],
            'proof' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp,application/pdf', 'max:2048'],
        ]);

        DB::beginTransaction();
        try {
            /** @var Transaction $locked */
            $locked = Transaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            /** @var Payment $payment */
            $payment = $this->service->addPayment($locked, $data['method'], (string) $data['amount'], $data['reference_no'] ?? null);

            $proof = $request->file('proof');
            if ($proof && $proof->getError() === UPLOAD_ERR_OK && $proof->getPathname()) {
                $name = 'proofs/'.\Illuminate\Support\Str::random(40).'.'.$proof->getClientOriginalExtension();
                $stream = fopen($proof->getPathname(), 'r');
                \Storage::disk('public')->put($name, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
                $payment->forceFill([
                    'proof_path' => $name,
                ])->save();
            }

            DB::commit();
        } catch (InvalidArgumentException $e) {
            DB::rollBack();

            return redirect()
                ->route('transactions.show', $transaction)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', __('ui.payment_added'));
    }

    public function void(Transaction $transaction): RedirectResponse
    {
        $this->authorize('void', $transaction);

        try {
            $this->service->void($transaction);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('transactions.show', $transaction)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', __('ui.transaction_voided_ok'));
    }
}
