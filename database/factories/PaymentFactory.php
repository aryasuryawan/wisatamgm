<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'method' => fake()->randomElement(Payment::METHODS),
            'amount' => fake()->numberBetween(100_000, 2_000_000),
            'paid_at' => now(),
            'reference_no' => null,
        ];
    }
}
