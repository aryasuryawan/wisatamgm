<?php

namespace App\Domain\Schedule\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\Customer;
use App\Models\Schedule;
use App\Models\ScheduleParticipant;
use App\Models\ScheduleStaff;
use App\Models\User;
use InvalidArgumentException;

class ScheduleService
{
    private const TRANSITIONS = [
        'draft' => ['confirmed', 'cancelled'],
        'confirmed' => ['ongoing', 'cancelled'],
        'ongoing' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function createSchedule(array $data): Schedule
    {
        /** @var Schedule $schedule */
        $schedule = Schedule::create($data);

        AuditLogger::log('schedule.created', $schedule, null, $schedule->toArray());

        return $schedule;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSchedule(Schedule $schedule, array $data): void
    {
        $before = $schedule->toArray();

        if ($schedule->status === 'completed' || $schedule->status === 'cancelled') {
            throw new InvalidArgumentException(__('ui.schedule_status_locked'));
        }

        $schedule->fill($data)->save();

        AuditLogger::log('schedule.updated', $schedule, $before, $schedule->toArray());
    }

    public function deleteSchedule(Schedule $schedule): void
    {
        if (! in_array($schedule->status, ['draft', 'cancelled'], true)) {
            throw new InvalidArgumentException(__('ui.schedule_delete_blocked'));
        }

        $before = $schedule->toArray();

        $schedule->participants()->delete();
        $schedule->staff()->delete();
        $schedule->delete();

        AuditLogger::log('schedule.deleted', $schedule, $before, null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function changeStatus(Schedule $schedule, string $newStatus): void
    {
        if ($schedule->status === $newStatus) {
            return;
        }

        $allowed = self::TRANSITIONS[$schedule->status] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            throw new InvalidArgumentException(__('ui.schedule_invalid_transition', [
                'from' => __('ui.status_' . $schedule->status),
                'to' => __('ui.status_' . $newStatus),
            ]));
        }

        $before = ['status' => $schedule->status];
        $schedule->forceFill(['status' => $newStatus])->save();

        AuditLogger::log('schedule.status_changed', $schedule, $before, ['status' => $newStatus]);
    }

    public function addParticipant(Schedule $schedule, int $customerId): ScheduleParticipant
    {
        if ($schedule->participants()->where('customer_id', $customerId)->exists()) {
            throw new InvalidArgumentException(__('ui.participant_duplicate'));
        }

        if ($schedule->seatsLeft() < 1) {
            throw new InvalidArgumentException(__('ui.schedule_full'));
        }

        /** @var ScheduleParticipant $participant */
        $participant = $schedule->participants()->create(['customer_id' => $customerId]);

        AuditLogger::log('schedule.participant_added', $participant);

        return $participant;
    }

    public function removeParticipant(ScheduleParticipant $participant): void
    {
        $after = $participant->only(['schedule_id', 'customer_id']);
        $participant->delete();

        AuditLogger::log('schedule.participant_removed', $participant, null, $after);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addStaff(Schedule $schedule, User $user, string $roleInTrip): ScheduleStaff
    {
        if ($schedule->staff()->where('user_id', $user->id)->exists()) {
            throw new InvalidArgumentException(__('ui.staff_duplicate'));
        }

        /** @var ScheduleStaff $staff */
        $staff = $schedule->staff()->create([
            'user_id' => $user->id,
            'role_in_trip' => in_array($roleInTrip, ScheduleStaff::ROLES, true) ? $roleInTrip : 'guide',
        ]);

        AuditLogger::log('schedule.staff_added', $staff);

        return $staff;
    }

    public function removeStaff(ScheduleStaff $staff): void
    {
        $after = $staff->only(['schedule_id', 'user_id', 'role_in_trip']);
        $staff->delete();

        AuditLogger::log('schedule.staff_removed', $staff, null, $after);
    }

    public function findCustomer(int $id): Customer
    {
        return Customer::findOrFail($id);
    }

    public function findUser(int $id): User
    {
        return User::findOrFail($id);
    }
}
