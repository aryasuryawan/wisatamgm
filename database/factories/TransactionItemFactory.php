<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionItem>
 */
class TransactionItemFactory extends Factory
{
    protected $model = TransactionItem::class;

    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'product_id' => Product::factory(),
            'qty' => fake()->numberBetween(1, 3),
            'price' => fake()->numberBetween(50_000, 2_000_000),
            'schedule_id' => null,
            'equipment_unit_id' => null,
        ];
    }
}
