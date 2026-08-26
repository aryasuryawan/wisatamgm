<?php

namespace Database\Factories;

use App\Models\EmailLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailLog>
 */
class EmailLogFactory extends Factory
{
    protected $model = EmailLog::class;

    public function definition(): array
    {
        return [
            'customer_id' => null,
            'transaction_id' => null,
            'email' => fake()->safeEmail(),
            'subject' => fake()->words(3, true),
            'status' => 'sent',
            'provider_ref' => null,
            'error_message' => null,
            'sent_at' => now(),
        ];
    }
}
