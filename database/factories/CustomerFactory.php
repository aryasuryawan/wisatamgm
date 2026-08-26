<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'nationality_type' => fake()->randomElement(['indonesia', 'international']),
            'source' => fake()->randomElement(['organic', 'ads', 'referral', 'walk_in', 'other']),
            'segment_tag' => null,
            'notes' => null,
            'preferences' => null,
            'total_orders' => 0,
            'total_spent' => 0,
        ];
    }

    public function indonesian(): static
    {
        return $this->state(fn () => [
            'nationality_type' => 'indonesia',
        ]);
    }

    public function international(): static
    {
        return $this->state(fn () => [
            'nationality_type' => 'international',
        ]);
    }

    public function vip(): static
    {
        return $this->state(fn () => [
            'total_orders' => 10,
            'total_spent' => 5000000,
        ]);
    }

    public function repeat(): static
    {
        return $this->state(fn () => [
            'total_orders' => 3,
            'total_spent' => 1500000,
        ]);
    }
}
