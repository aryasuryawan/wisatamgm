<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerCertification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerCertification>
 */
class CustomerCertificationFactory extends Factory
{
    protected $model = CustomerCertification::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'agency' => fake()->randomElement(['PADI', 'SSI', 'NAUI', 'SDI', 'TDI']),
            'level' => fake()->randomElement(['OW', 'AOW', 'Rescue', 'Divemaster', 'Instructor']),
            'cert_number' => fake()->bothify('??-#####'),
            'cert_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'expiry_date' => fake()->optional()->dateTimeBetween('+1 year', '+5 years'),
        ];
    }
}
