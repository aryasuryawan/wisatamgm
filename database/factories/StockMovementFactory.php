<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        $qty = fake()->numberBetween(1, 50);

        return [
            'branch_id' => Branch::factory(),
            'product_id' => Product::factory(),
            'type' => fake()->randomElement(['in', 'out', 'adjustment', 'opname']),
            'qty' => $qty,
            'qty_after' => $qty,
            'ref_type' => null,
            'ref_id' => null,
            'unit_cost' => fake()->optional()->randomBetween(10000, 500000),
            'notes' => fake()->sentence(),
            'user_id' => User::factory(),
        ];
    }
}
