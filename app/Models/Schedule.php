<?php

namespace App\Models;

use Database\Factories\ScheduleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    /** @use HasFactory<ScheduleFactory> */
    use HasFactory;

    public const STATUSES = ['draft', 'confirmed', 'ongoing', 'completed', 'cancelled'];

    protected $fillable = [
        'branch_id',
        'product_id',
        'date_start',
        'date_end',
        'capacity',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_start' => 'datetime',
            'date_end' => 'datetime',
            'capacity' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ScheduleParticipant::class);
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'schedule_participants')->withTimestamps();
    }

    public function staff(): HasMany
    {
        return $this->hasMany(ScheduleStaff::class);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('date_start', '>=', now());
    }

    public function scopeForBranches(Builder $query, array $branchIds): Builder
    {
        return $query->whereIn('branch_id', $branchIds);
    }

    public function seatsLeft(): int
    {
        return max(0, $this->capacity - $this->participants()->count());
    }
}
