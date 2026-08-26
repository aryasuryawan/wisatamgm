<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('schedules.view');
    }

    public function view(User $user, Schedule $schedule): bool
    {
        if (! $user->can('schedules.view')) {
            return false;
        }

        return $this->withinScope($user, $schedule);
    }

    public function create(User $user): bool
    {
        return $user->can('schedules.create');
    }

    public function update(User $user, Schedule $schedule): bool
    {
        if (! $user->can('schedules.edit')) {
            return false;
        }

        return $this->withinScope($user, $schedule);
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        if (! $user->can('schedules.delete')) {
            return false;
        }

        return $this->withinScope($user, $schedule);
    }

    private function withinScope(User $user, Schedule $schedule): bool
    {
        if ($user->hasRole('owner')) {
            return true;
        }

        // dive-guide hanya jadwal yang ditugaskan ke dia.
        if ($user->hasRole('dive-guide')) {
            return $schedule->staff()->where('user_id', $user->id)->exists();
        }

        return $user->branches()->pluck('branches.id')->contains($schedule->branch_id);
    }
}
