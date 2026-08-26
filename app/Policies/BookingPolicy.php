<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('bookings.view');
    }

    public function create(User $user): bool
    {
        return $user->can('bookings.create');
    }

    public function update(User $user, Booking $booking): bool
    {
        // Booking yang sudah check-in tidak boleh diubah datanya.
        return in_array($booking->status, ['confirmed'])
            && $user->can('bookings.edit');
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return in_array($booking->status, ['confirmed', 'checked_in'])
            && $user->can('bookings.edit');
    }

    public function checkOut(User $user, Booking $booking): bool
    {
        return $booking->status === 'checked_in' && $user->can('bookings.edit');
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $booking->status === 'cancelled' && $user->can('bookings.delete');
    }

    public function pay(User $user, Booking $booking): bool
    {
        return ! in_array($booking->status, ['cancelled'])
            && ($user->can('bookings.edit') || $user->can('transactions.create'));
    }
}
