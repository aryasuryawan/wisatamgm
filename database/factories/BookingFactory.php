<?php

namespace Database\Factories;

use App\Models\BookableUnit;
use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $start = Carbon::instance(fake()->dateTimeBetween('+1 day', '+20 days'));

        return [
            'branch_id' => fn (array $attrs) => BookableUnit::findOrFail($attrs['bookable_unit_id'])->branch_id,
            'bookable_unit_id' => BookableUnit::factory(),
            'customer_id' => null,
            'transaction_id' => null,
            'user_id' => User::factory(),
            'guest_name' => fake()->name(),
            'guest_phone' => fake()->numerify('0812########'),
            'guests_count' => 2,
            'date_start' => $start->toDateString(),
            'date_end' => $start->copy()->addDays(2)->toDateString(),
            'amount_total' => 900000,
            'status' => 'confirmed',
            'notes' => null,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled', 'cancelled_at' => now()]);
    }

    public function checkedIn(): static
    {
        return $this->state(fn () => ['status' => 'checked_in', 'checked_in_at' => now()]);
    }

    public function checkedOut(): static
    {
        return $this->state(fn () => [
            'status' => 'checked_out',
            'checked_in_at' => now()->subDays(2),
            'checked_out_at' => now(),
        ]);
    }
}
