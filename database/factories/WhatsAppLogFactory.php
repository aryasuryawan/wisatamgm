<?php

namespace Database\Factories;

use App\Models\WhatsAppLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsAppLog>
 */
class WhatsAppLogFactory extends Factory
{
    protected $model = WhatsAppLog::class;

    public function definition(): array
    {
        return [
            'customer_id' => null,
            'transaction_id' => null,
            'schedule_id' => null,
            'phone' => fake()->numerify('+6281#########'),
            'type' => 'manual',
            'message' => fake()->sentence(),
            'status' => 'sent',
            'provider_ref' => null,
            'error_message' => null,
            'sent_at' => now(),
        ];
    }
}
