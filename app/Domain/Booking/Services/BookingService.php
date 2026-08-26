<?php

namespace App\Domain\Booking\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Transaction\Services\TransactionService;
use App\Models\Booking;
use App\Models\BookableUnit;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(private TransactionService $transactions)
    {
    }

    /**
     * Cek ketersediaan unit pada rentang [start, end). Return true jika bebas.
     */
    public function isAvailable(BookableUnit $unit, string $dateStart, string $dateEnd, ?int $ignoreBookingId = null): bool
    {
        return ! Booking::where('bookable_unit_id', $unit->id)
            ->where('status', '!=', 'cancelled')
            ->when($ignoreBookingId, fn ($q) => $q->where('id', '!=', $ignoreBookingId))
            ->whereDate('date_start', '<', $dateEnd)
            ->whereDate('date_end', '>', $dateStart)
            ->exists();
    }

    public function create(User $actor, array $data): Booking
    {
        $unit = BookableUnit::findOrFail($data['bookable_unit_id']);

        if (! $unit->is_active) {
            throw ValidationException::withMessages([
                'bookable_unit_id' => __('messages.booking_unit_inactive'),
            ]);
        }

        if ((int) $data['guests_count'] > $unit->capacity) {
            throw ValidationException::withMessages([
                'guests_count' => __('messages.booking_capacity_exceeded', ['max' => $unit->capacity]),
            ]);
        }

        if (! $this->isAvailable($unit, $data['date_start'], $data['date_end'])) {
            throw ValidationException::withMessages([
                'date_start' => __('messages.booking_unavailable'),
            ]);
        }

        $booking = Booking::create($this->prepare($data, $actor));

        AuditLogger::log('booking_created', $booking, null, [
            'unit' => $unit->name,
            'guest' => $booking->guest_name,
            'from' => $booking->date_start->toDateString(),
            'to' => $booking->date_end->toDateString(),
            'amount' => $booking->amount_total,
        ]);

        return $booking;
    }

    public function update(Booking $booking, User $actor, array $data): Booking
    {
        $unit = BookableUnit::findOrFail($data['bookable_unit_id']);

        if ((int) $data['guests_count'] > $unit->capacity) {
            throw ValidationException::withMessages([
                'guests_count' => __('messages.booking_capacity_exceeded', ['max' => $unit->capacity]),
            ]);
        }

        if (! $this->isAvailable($unit, $data['date_start'], $data['date_end'], $booking->id)) {
            throw ValidationException::withMessages([
                'date_start' => __('messages.booking_unavailable'),
            ]);
        }

        $before = $booking->only(['bookable_unit_id', 'guest_name', 'guests_count', 'date_start', 'date_end', 'amount_total']);

        $booking->update($this->prepare($data, $actor));

        AuditLogger::log('booking_updated', $booking, $before, $booking->only(array_keys($before)));

        return $booking;
    }

    public function cancel(Booking $booking, User $actor): void
    {
        if ($booking->status === 'checked_out') {
            throw ValidationException::withMessages(['status' => __('messages.booking_already_checked_out')]);
        }
        if ($booking->status === 'cancelled') {
            throw ValidationException::withMessages(['status' => __('messages.booking_already_cancelled')]);
        }

        $before = $booking->only(['status', 'cancelled_at']);
        $booking->forceFill([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ])->save();

        // Tanggal langsung bebas kembali (query availability mengabaikan cancelled).
        AuditLogger::log('booking_cancelled', $booking, $before, ['status' => 'cancelled']);
    }

    public function checkIn(Booking $booking, User $actor): void
    {
        if ($booking->status !== 'confirmed') {
            throw ValidationException::withMessages(['status' => __('messages.booking_invalid_transition')]);
        }

        $booking->forceFill([
            'status' => 'checked_in',
            'checked_in_at' => now(),
        ])->save();

        AuditLogger::log('booking_checked_in', $booking, ['status' => 'confirmed'], ['status' => 'checked_in']);
    }

    public function checkOut(Booking $booking, User $actor): void
    {
        if ($booking->status !== 'checked_in') {
            throw ValidationException::withMessages(['status' => __('messages.booking_invalid_transition')]);
        }

        $booking->forceFill([
            'status' => 'checked_out',
            'checked_out_at' => now(),
        ])->save();

        AuditLogger::log('booking_checked_out', $booking, ['status' => 'checked_in'], ['status' => 'checked_out']);
    }

    /**
     * Pembayaran booking SELALU lewat transaksi POS (aturan laba-rugi satu
     * sumber data). Transaksi pertama dibuat otomatis memakai produk unit,
     * qty = jumlah malam; pembayaran berikutnya jadi split payment.
     */
    public function recordPayment(
        Booking $booking,
        User $actor,
        string $method,
        float $amount,
        ?string $referenceNo = null,
    ): \App\Models\Transaction {
        if ($booking->status === 'cancelled') {
            throw ValidationException::withMessages(['status' => __('messages.booking_cancelled_no_payment')]);
        }

        $transaction = $booking->transaction;

        $previousUser = auth()->user();
        auth()->login($actor);

        try {
            if (! $transaction) {
                $transaction = $this->transactions->create(
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

                // Backdate ke tanggal check-in agar laporan periode menginap akurat.
                $transaction->forceFill(['transaction_date' => $booking->date_start->copy()->setTime(12, 0)])->save();
                $booking->forceFill(['transaction_id' => $transaction->id])->save();
            }

            $remaining = $this->transactions->remaining($transaction);

            if ((float) $amount > (float) $remaining) {
                throw ValidationException::withMessages([
                    'amount' => __('ui.payment_overpay'),
                ]);
            }

            $payment = $this->transactions->addPayment($transaction, $method, $amount, $referenceNo);
        } finally {
            if ($previousUser) {
                auth()->setUser($previousUser);
            }
        }

        AuditLogger::log('booking_payment_recorded', $booking, null, [
            'transaction_id' => $transaction->id,
            'method' => $method,
            'amount' => $amount,
        ]);

        return $transaction;
    }

    private function prepare(array $data, User $actor): array
    {
        unset($data['transaction_id'], $data['status']);

        $data['branch_id'] = BookableUnit::findOrFail($data['bookable_unit_id'])->branch_id;
        $data['user_id'] = $actor->id;

        return $data;
    }
}
