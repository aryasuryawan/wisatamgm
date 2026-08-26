<?php

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'action' => 'test.action',
            'model_type' => null,
            'model_id' => null,
            'before' => null,
            'after' => ['foo' => 'bar'],
            'ip_address' => $this->faker->ipv4(),
        ];
    }
}
