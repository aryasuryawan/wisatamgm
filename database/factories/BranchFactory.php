<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
            'brand' => $this->faker->randomElement(['tulambenscuba', 'scubago', 'lainnya']),
            'domain' => $this->faker->optional()->domainName(),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'pic_user_id' => null,
            'is_active' => true,
        ];
    }
}
