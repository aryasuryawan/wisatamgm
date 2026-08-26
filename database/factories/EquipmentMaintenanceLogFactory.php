<?php

namespace Database\Factories;

use App\Models\EquipmentMaintenanceLog;
use App\Models\EquipmentUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentMaintenanceLog>
 */
class EquipmentMaintenanceLogFactory extends Factory
{
    protected $model = EquipmentMaintenanceLog::class;

    public function definition(): array
    {
        return [
            'equipment_unit_id' => EquipmentUnit::factory(),
            'date' => fake()->dateTimeBetween('-1 year', 'now'),
            'type' => fake()->randomElement(['routine', 'repair', 'inspection', 'replacement']),
            'description' => fake()->sentence(),
            'cost' => fake()->numberBetween(0, 500000),
            'performed_by' => User::factory(),
        ];
    }
}
