<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'category_id' => ProductCategory::factory(),
            'branch_id' => Branch::factory(),
            'name' => fake()->unique()->words(3, true),
            'base_price' => fake()->numberBetween(50000, 5000000),
            'unit' => fake()->randomElement(['pcs', 'orang', 'trip', 'hari', 'kg']),
            'stock_quantity' => fake()->numberBetween(0, 100),
            'is_active' => true,
            'meta' => null,
        ];
    }
}
