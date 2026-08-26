<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\EquipmentUnit;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentUnit>
 */
class EquipmentUnitFactory extends Factory
{
    protected $model = EquipmentUnit::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'branch_id' => Branch::factory(),
            'code' => fake()->unique()->bothify('EQ-??-#####'),
            'condition' => 'good',
            'status' => 'available',
            'notes' => null,
        ];
    }
}
