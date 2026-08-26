<?php

namespace App\Models;

use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    public const STATUSES = ['draft', 'confirmed', 'paid', 'partial', 'void'];

    protected $fillable = [
        'branch_id',
        'customer_id',
        'user_id',
        'transaction_date',
        'status',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'idempotency_key',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function scopeNotVoid(Builder $query): Builder
    {
        return $query->where('status', '!=', 'void');
    }

    public function paidTotal(): string
    {
        return (string) $this->payments()->sum('amount');
    }

    public function isFullyPaid(): bool
    {
        return bccomp($this->paidTotal(), (string) $this->grand_total, 2) >= 0;
    }
}
