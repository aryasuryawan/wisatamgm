<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 day', '+30 days');

        return [
            'branch_id' => Branch::factory(),
            'product_id' => Product::factory(),
            'date_start' => $start,
            'date_end' => (clone $start)->modify('+6 hours'),
            'capacity' => fake()->numberBetween(4, 12),
            'status' => 'draft',
            'notes' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => 'confirmed']);
    }

    public function ongoing(): static
    {
        return $this->state(fn () => ['status' => 'ongoing']);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => 'completed']);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }
}
