<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Schedule;
use App\Models\ScheduleParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleParticipant>
 */
class ScheduleParticipantFactory extends Factory
{
    protected $model = ScheduleParticipant::class;

    public function definition(): array
    {
        return [
            'schedule_id' => Schedule::factory(),
            'customer_id' => Customer::factory(),
            'transaction_item_id' => null,
        ];
    }
}
