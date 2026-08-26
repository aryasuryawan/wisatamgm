<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discount>
 */
class DiscountFactory extends Factory
{
    protected $model = Discount::class;

    public function definition(): array
    {
        return [
            'branch_id' => null,
            'code' => strtoupper(fake()->unique()->bothify('DISC-####')),
            'name' => fake()->words(3, true),
            'type' => 'percent',
            'value' => fake()->randomElement([5, 10, 15, 20]),
            'valid_from' => now()->subDay()->toDateString(),
            'valid_until' => now()->addMonth()->toDateString(),
            'usage_limit' => null,
            'usage_limit_per_customer' => null,
            'category_scope' => null,
            'is_active' => true,
        ];
    }

    public function nominal(int $amount): static
    {
        return $this->state(fn () => ['type' => 'nominal', 'value' => $amount]);
    }

    public function percent(int $percent): static
    {
        return $this->state(fn () => ['type' => 'percent', 'value' => $percent]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'valid_from' => now()->subDays(10)->toDateString(),
            'valid_until' => now()->subDay()->toDateString(),
        ]);
    }

    public function forBranch(Branch $branch): static
    {
        return $this->state(fn () => ['branch_id' => $branch->id]);
    }
}
