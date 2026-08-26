<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $subtotal = fake()->numberBetween(100_000, 5_000_000);
        $tax = round($subtotal * config('transactions.ppn.rate'), 2);

        return [
            'branch_id' => Branch::factory(),
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'transaction_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'status' => 'paid',
            'subtotal' => $subtotal,
            'discount_total' => 0,
            'tax_total' => $tax,
            'grand_total' => $subtotal + $tax,
            'idempotency_key' => fake()->unique()->uuid(),
            'notes' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => 'confirmed']);
    }

    public function partial(): static
    {
        return $this->state(fn () => ['status' => 'partial']);
    }

    public function voided(): static
    {
        return $this->state(fn () => ['status' => 'void']);
    }
}
