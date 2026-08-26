<?php

namespace App\Models;

use Database\Factories\ScheduleParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleParticipant extends Model
{
    /** @use HasFactory<ScheduleParticipantFactory> */
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'customer_id',
        'transaction_item_id',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
