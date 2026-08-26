<?php

namespace App\Models;

use Database\Factories\ScheduleStaffFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleStaff extends Model
{
    /** @use HasFactory<ScheduleStaffFactory> */
    use HasFactory;

    public const ROLES = ['guide', 'instructor', 'assistant', 'divemaster'];

    protected $fillable = [
        'schedule_id',
        'user_id',
        'role_in_trip',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
