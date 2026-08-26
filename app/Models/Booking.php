<?php

namespace App\Models;

use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    public const STATUSES = ['confirmed', 'checked_in', 'checked_out', 'cancelled'];

    protected $fillable = [
        'branch_id',
        'bookable_unit_id',
        'customer_id',
        'transaction_id',
        'user_id',
        'guest_name',
        'guest_phone',
        'guests_count',
        'date_start',
        'date_end',
        'amount_total',
        'status',
        'cancelled_at',
        'checked_in_at',
        'checked_out_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_start' => 'date',
            'date_end' => 'date',
            'amount_total' => 'decimal:2',
            'cancelled_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(BookableUnit::class, 'bookable_unit_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function nights(): int
    {
        return max(1, (int) $this->date_start->diffInDays($this->date_end));
    }

    public function paidTotal(): float
    {
        return $this->transaction ? (float) $this->transaction->paidTotal() : 0.0;
    }

    public function isBlocking(): bool
    {
        return $this->status !== 'cancelled';
    }

    public function scopeForBranches(Builder $query, array $branchIds): Builder
    {
        return $query->whereIn('branch_id', $branchIds);
    }
}
