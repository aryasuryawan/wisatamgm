<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\ScheduleStaff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleStaff>
 */
class ScheduleStaffFactory extends Factory
{
    protected $model = ScheduleStaff::class;

    public function definition(): array
    {
        return [
            'schedule_id' => Schedule::factory(),
            'user_id' => User::factory(),
            'role_in_trip' => fake()->randomElement(ScheduleStaff::ROLES),
        ];
    }
}
