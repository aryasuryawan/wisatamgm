<?php

namespace App\Http\Controllers\Booking;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Booking\Services\BookingService;
use App\Http\Controllers\Controller;
use App\Models\BookableUnit;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(private BookingService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Booking::class);

        $query = Booking::with(['unit.product', 'customer', 'transaction'])
            ->orderByDesc('date_start');

        $query = $this->applyBranchScope($query);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($unitId = $request->input('bookable_unit_id')) {
            $query->where('bookable_unit_id', $unitId);
        }

        return view('booking.index', [
            'bookings' => $query->paginate(20)->withQueryString(),
            'units' => $this->availableUnits(),
            'availability' => null,
        ]);
    }

    public function calendar(Request $request): View
    {
        $this->authorize('viewAny', Booking::class);

        try {
            $month = Carbon::parse($request->input('month', now()->format('Y-m')).'-01');
        } catch (\Throwable) {
            $month = now()->startOfMonth();
        }

        $rangeStart = $month->copy()->startOfMonth();
        $rangeEnd = $month->copy()->endOfMonth();
        $daysInMonth = $rangeEnd->day;

        $unitsQuery = BookableUnit::with(['branch', 'product'])
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name');

        if (auth()->user()->hasRole('admin-cabang')) {
            $branchIds = auth()->user()->branches()->pluck('branches.id');
            $unitsQuery->forBranches($branchIds->all());
        } elseif ($branchId = $request->input('branch_id')) {
            $unitsQuery->where('branch_id', $branchId);
        }

        if ($type = $request->input('type')) {
            $unitsQuery->where('type', $type);
        }

        $units = $unitsQuery->get();

        // Booking yang menyentuh rentang bulan ini.
        $bookings = Booking::query()
            ->whereIn('bookable_unit_id', $units->pluck('id'))
            ->where('status', '!=', 'cancelled')
            ->whereDate('date_start', '<=', $rangeEnd)
            ->whereDate('date_end', '>=', $rangeStart)
            ->get()
            ->groupBy('bookable_unit_id');

        return view('booking.calendar', [
            'month' => $month,
            'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'daysInMonth' => $daysInMonth,
            'units' => $units,
            'bookingsByUnit' => $bookings,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Booking::class);

        return view('booking.create', [
            'units' => $this->editableUnits(),
            'customers' => Customer::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Booking::class);

        $data = $this->validated($request);
        $this->assertAllowedBranch((int) BookableUnit::findOrFail($data['bookable_unit_id'])->branch_id);

        $booking = $this->service->create($request->user(), $data);

        return redirect()
            ->route('bookings.show', $booking)
            ->with('success', __('ui.booking_created'));
    }

    public function show(Request $request, Booking $booking): View
    {
        $this->authorize('viewAny', Booking::class);

        $booking->load(['unit.product', 'customer', 'transaction.payments']);

        // Cek ketersediaan cepat dari form di halaman show.
        $availability = null;
        if ($request->filled('check_start') && $request->filled('check_end')) {
            $availability = [
                'start' => $request->input('check_start'),
                'end' => $request->input('check_end'),
                'free' => $this->service->isAvailable($booking->unit, $request->input('check_start'), $request->input('check_end')),
            ];
        }

        return view('booking.show', [
            'booking' => $booking,
            'availability' => $availability,
            'methods' => Payment::METHODS,
        ]);
    }

    public function edit(Booking $booking): View
    {
        $this->authorize('update', $booking);

        return view('booking.edit', [
            'booking' => $booking,
            'units' => $this->editableUnits(),
            'customers' => Customer::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('update', $booking);

        $data = $this->validated($request);

        $this->service->update($booking, $request->user(), $data);

        return redirect()
            ->route('bookings.show', $booking)
            ->with('success', __('ui.booking_updated'));
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        $this->authorize('delete', $booking);

        AuditLogger::log('booking_deleted', $booking, $booking->toArray(), null);

        $booking->delete();

        return redirect()
            ->route('bookings.index')
            ->with('success', __('ui.booking_deleted'));
    }

    public function cancel(Booking $booking, Request $request): RedirectResponse
    {
        $this->authorize('cancel', $booking);

        $this->service->cancel($booking, $request->user());

        return redirect()
            ->route('bookings.show', $booking)
            ->with('success', __('ui.booking_cancelled_msg'));
    }

    public function checkIn(Booking $booking, Request $request): RedirectResponse
    {
        $this->authorize('update', $booking);

        $this->service->checkIn($booking, $request->user());

        return redirect()
            ->route('bookings.show', $booking)
            ->with('success', __('ui.booking_checked_in'));
    }

    public function checkOut(Booking $booking, Request $request): RedirectResponse
    {
        $this->authorize('checkOut', $booking);

        $this->service->checkOut($booking, $request->user());

        return redirect()
            ->route('bookings.show', $booking)
            ->with('success', __('ui.booking_checked_out'));
    }

    public function addPayment(Booking $booking, Request $request): RedirectResponse
    {
        $this->authorize('pay', $booking);

        $data = $request->validate([
            'method' => ['required', 'in:cash,transfer,qris,card'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference_no' => ['nullable', 'string', 'max:64'],
            'proof' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp,application/pdf', 'max:2048'],
        ]);

        $transaction = $this->service->recordPayment(
            $booking,
            $request->user(),
            $data['method'],
            (float) $data['amount'],
            $data['reference_no'] ?? null,
        );

        $proof = $request->file('proof');
        if ($proof && $proof->getError() === UPLOAD_ERR_OK && $proof->getPathname()) {
            $name = 'proofs/'.\Illuminate\Support\Str::random(40).'.'.$proof->getClientOriginalExtension();
            $stream = fopen($proof->getPathname(), 'r');
            \Storage::disk('public')->put($name, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            $payment = $transaction->payments()->orderByDesc('id')->first();
            $payment?->forceFill([
                'proof_path' => $name,
            ])->save();
        }

        return redirect()
            ->route('bookings.show', $booking)
            ->with('success', __('ui.booking_payment_recorded'));
    }

    // ------------------------------------------------------------------

    private function validated(Request $request): array
    {
        return $request->validate([
            'bookable_unit_id' => ['required', 'exists:bookable_units,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:32'],
            'guests_count' => ['required', 'integer', 'min:1', 'max:500'],
            'date_start' => ['required', 'date'],
            'date_end' => ['required', 'date', 'after:date_start'],
            'amount_total' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function applyBranchScope($query)
    {
        if (! auth()->user()->hasRole('admin-cabang')) {
            return $query;
        }

        $branchIds = auth()->user()->branches()->pluck('branches.id');

        return $query->whereIn('branch_id', $branchIds);
    }

    private function availableUnits()
    {
        if (auth()->user()->hasRole('admin-cabang')) {
            $branchIds = auth()->user()->branches()->pluck('branches.id');

            return BookableUnit::forBranches($branchIds->all())->with('branch')->orderBy('name')->get();
        }

        return BookableUnit::with('branch')->orderBy('name')->get();
    }

    private function editableUnits()
    {
        if (auth()->user()->hasRole('admin-cabang')) {
            $branchIds = auth()->user()->branches()->pluck('branches.id');

            return BookableUnit::forBranches($branchIds->all())
                ->where('is_active', true)->with('product')->orderBy('name')->get();
        }

        return BookableUnit::where('is_active', true)->with('product')->orderBy('name')->get();
    }

    private function assertAllowedBranch(int $branchId): void
    {
        if (auth()->user()->hasRole('admin-cabang')) {
            $allowed = auth()->user()->branches()->pluck('branches.id')->contains($branchId);

            abort_unless($allowed, 403);
        }
    }
}
