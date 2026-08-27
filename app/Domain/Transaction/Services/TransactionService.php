<?php

namespace App\Domain\Transaction\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Discount\Services\DiscountService;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Notification\Services\EmailService;
use App\Domain\Notification\Services\WhatsAppService;
use App\Domain\Schedule\Services\ScheduleService;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class TransactionService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly ScheduleService $scheduleService,
        private readonly DiscountService $discountService,
        private readonly WhatsAppService $whatsapp,
        private readonly EmailService $emails,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data          branch_id, customer_id?, notes?, idempotency_key?
     * @param  list<array{product_id: int, qty: int, schedule_id?: ?int, equipment_unit_id?: ?int}>  $items
     * @param  list<array{method: string, amount: float|string, reference_no?: ?string}>  $payments
     */
    public function create(array $data, array $items, array $payments = []): Transaction
    {
        if (! empty($data['idempotency_key'])) {
            $existing = Transaction::where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                return $existing;
            }
        }

        if ($items === []) {
            throw new InvalidArgumentException(__('ui.transaction_empty_items'));
        }

        return DB::transaction(function () use ($data, $items, $payments) {
            /** @var list<array{product: Product, qty: int, price: string, schedule_id: ?int, equipment_unit_id: ?int}> $resolved */
            $resolved = [];
            $subtotal = '0';

            foreach ($items as $item) {
                /** @var Product $product */
                $product = Product::query()->lockForUpdate()->findOrFail($item['product_id']);

                $qty = max(1, (int) ($item['qty'] ?? 1));
                // Harga SELALU dari server — input harga dari client diabaikan.
                $price = (string) $product->base_price;
                $subtotal = bcadd($subtotal, bcmul($price, (string) $qty, 2), 2);

                $resolved[] = [
                    'product' => $product,
                    'qty' => $qty,
                    'price' => $price,
                    'schedule_id' => $item['schedule_id'] ?? null,
                    'equipment_unit_id' => $item['equipment_unit_id'] ?? null,
                ];
            }

            $discount = min(max(0, (string) ($data['discount_total'] ?? '0')), $subtotal);

            // Kode diskon menggantikan diskon manual — dihitung & divalidasi server-side.
            $appliedDiscount = null;
            if (! empty($data['discount_code'])) {
                $lines = array_map(
                    fn (array $row) => [
                        'type_slug' => $row['product']->category?->type_slug,
                        'line_total' => bcmul($row['price'], (string) $row['qty'], 2),
                    ],
                    $resolved
                );

                $result = $this->discountService->resolveAndCalculate(
                    $data['discount_code'],
                    $lines,
                    $data['customer_id'] ?? null,
                    (int) $data['branch_id'],
                );

                $discount = $result['amount'];
                $appliedDiscount = $result['discount'];
            }

            $taxable = bcsub($subtotal, $discount, 2);
            $tax = $this->calculateTax($taxable);
            $grandTotal = bcadd($taxable, $tax, 2);

            /** @var Transaction $transaction */
            $transaction = Transaction::create([
                'branch_id' => $data['branch_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'user_id' => auth()->id(),
                'transaction_date' => $data['transaction_date'] ?? now(),
                'status' => 'draft',
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'tax_total' => $tax,
                'grand_total' => $grandTotal,
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($resolved as $row) {
                /** @var TransactionItem $item */
                $item = $transaction->items()->create([
                    'product_id' => $row['product']->id,
                    'qty' => $row['qty'],
                    'price' => $row['price'],
                    'schedule_id' => $row['schedule_id'],
                    'equipment_unit_id' => $row['equipment_unit_id'],
                ]);

                $this->releaseStockIfNeeded($row['product'], $row['qty'], $transaction->id);
                $this->linkScheduleParticipant($item, $transaction);
            }

            if ($appliedDiscount !== null) {
                $this->discountService->recordUsage($appliedDiscount, $transaction, $discount);
            }

            foreach ($payments as $payment) {
                $this->addPayment($transaction, $payment['method'], (string) $payment['amount'], $payment['reference_no'] ?? null);
            }

            if ($transaction->status === 'draft') {
                $transaction->forceFill(['status' => 'confirmed'])->save();
            }

            $transaction->refresh();

            AuditLogger::log('transaction.created', $transaction, null, $transaction->toArray());

            return $transaction;
        });
    }

    public function addPayment(Transaction $transaction, string $method, string $amount, ?string $referenceNo = null): Payment
    {
        if ($transaction->status === 'void') {
            throw new InvalidArgumentException(__('ui.transaction_voided'));
        }

        if (! in_array($method, Payment::METHODS, true)) {
            throw new InvalidArgumentException(__('ui.payment_invalid_method'));
        }

        if (bccomp($amount, '0', 2) <= 0) {
            throw new InvalidArgumentException(__('ui.payment_invalid_amount'));
        }

        $remaining = $this->remaining($transaction);
        if (bccomp($amount, $remaining, 2) > 0) {
            throw new InvalidArgumentException(__('ui.payment_overpay'));
        }

        $wasPaid = $transaction->status === 'paid';

        /** @var Payment $payment */
        $payment = $transaction->payments()->create([
            'method' => $method,
            'amount' => $amount,
            'paid_at' => now(),
            'reference_no' => $referenceNo,
        ]);

        $this->refreshStatus($transaction);

        if (! $wasPaid && $transaction->status === 'paid') {
            $this->applyPaidCounters($transaction);
            $this->notifyPaid($payment, $transaction);
        }

        return $payment;
    }

    public function remaining(Transaction $transaction): string
    {
        return max(0, bcsub((string) $transaction->grand_total, $transaction->paidTotal(), 2));
    }

    public function void(Transaction $transaction): void
    {
        if ($transaction->status === 'void') {
            throw new InvalidArgumentException(__('ui.transaction_already_void'));
        }

        DB::transaction(function () use ($transaction) {
            $before = $transaction->toArray();

            foreach ($transaction->items()->with('product.category')->get() as $item) {
                $slug = $item->product->category?->type_slug;
                if (in_array($slug, config('transactions.stockable_category_slugs'), true)) {
                    $this->stockService->stockIn($item->product, $item->qty, 'Void POS', 'transaction_void', $transaction->id);
                }
            }

            if ($transaction->status === 'paid') {
                $this->revertPaidCounters($transaction);
            }

            $transaction->forceFill(['status' => 'void'])->save();

            AuditLogger::log('transaction.void', $transaction, $before, $transaction->fresh()?->toArray());
        });
    }

    public function calculateTax(string $taxable): string
    {
        if (! config('transactions.ppn.enabled')) {
            return '0.00';
        }

        return bcmul($taxable, (string) config('transactions.ppn.rate'), 2);
    }

    private function refreshStatus(Transaction $transaction): void
    {
        $paid = $transaction->paidTotal();
        $grand = (string) $transaction->grand_total;

        $status = match (true) {
            bccomp($paid, $grand, 2) >= 0 => 'paid',
            bccomp($paid, '0', 2) > 0 => 'partial',
            default => $transaction->status === 'void' ? 'void' : 'confirmed',
        };

        if ($status !== $transaction->status) {
            $transaction->forceFill(['status' => $status])->save();
        }
    }

    private function applyPaidCounters(Transaction $transaction): void
    {
        if (! $transaction->customer_id) {
            return;
        }

        Customer::whereKey($transaction->customer_id)->increment('total_orders', 1);
        Customer::whereKey($transaction->customer_id)->increment('total_spent', $transaction->grand_total);
    }

    private function revertPaidCounters(Transaction $transaction): void
    {
        if (! $transaction->customer_id) {
            return;
        }

        Customer::whereKey($transaction->customer_id)->decrement('total_orders', 1);
        Customer::whereKey($transaction->customer_id)->decrement('total_spent', $transaction->grand_total);
    }

    private function releaseStockIfNeeded(Product $product, int $qty, int $transactionId): void
    {
        $slug = $product->category?->type_slug;

        if (in_array($slug, config('transactions.stockable_category_slugs'), true)) {
            $this->stockService->stockOut($product, $qty, 'POS', 'transaction', $transactionId);
        }
    }

    private function linkScheduleParticipant(TransactionItem $item, Transaction $transaction): void
    {
        if (! $item->schedule_id || ! $transaction->customer_id) {
            return;
        }

        $schedule = $item->schedule()->first();
        if (! $schedule) {
            return;
        }

        try {
            $participant = $this->scheduleService->addParticipant($schedule, $transaction->customer_id);
            $participant->forceFill(['transaction_item_id' => $item->id])->save();
        } catch (InvalidArgumentException) {
            // Peserta sudah terdaftar di jadwal ini — biarkan, transaksi tetap sah.
        }
    }

    /**
     * Konfirmasi WA + invoice email ke pelanggan. Dijalankan saat transaksi
     * baru berubah menjadi paid; semua pengiriman lewat queue (aturan proyek).
     */
    private function notifyPaid(Payment $payment, Transaction $transaction): void
    {
        $customer = $transaction->customer()->first();

        if (! $customer) {
            return;
        }

        $invoice = [
            'transaction_no' => $transaction->id,
            'date' => optional($transaction->transaction_date)->format('d M Y H:i'),
            'grand_total' => (float) $transaction->grand_total,
            'paid_total' => (float) $payment->amount,
            'customer_name' => $customer->name,
        ];

        if ($customer->phone) {
            $template = \App\Models\Setting::get('wa_invoice_paid', __('messages.wa_invoice_paid'));
            $message = str_replace(
                [':name', ':no', ':total'],
                [$customer->name, $transaction->id, number_format((float) $transaction->grand_total, 0, ',', '.')],
                $template
            );
            $this->whatsapp->queue(
                phone: $customer->phone,
                message: $message,
                type: 'invoice_paid',
                customer: $customer,
                transaction: $transaction,
            );
        }

        if ($customer->email) {
            $subjectTemplate = \App\Models\Setting::get('email_invoice_subject', __('messages.email_invoice_subject'));
            $subject = str_replace(':no', $transaction->id, $subjectTemplate);
            $this->emails->queueInvoice(
                $customer,
                $transaction,
                subject: $subject,
                invoiceData: $invoice,
            );
        }
    }
}
