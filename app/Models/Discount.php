<?php

namespace App\Models;

use Database\Factories\DiscountFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    /** @use HasFactory<DiscountFactory> */
    use HasFactory;

    public const TYPES = ['nominal', 'percent'];

    protected $fillable = [
        'branch_id',
        'code',
        'name',
        'type',
        'value',
        'valid_from',
        'valid_until',
        'usage_limit',
        'usage_limit_per_customer',
        'category_scope',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'category_scope' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(DiscountUsage::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeValidAt(Builder $query, ?string $date = null): Builder
    {
        $date ??= now()->toDateString();

        return $query->active()
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', $date))
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', $date));
    }

    public function label(): string
    {
        return $this->type === 'percent'
            ? $this->code . ' (-' . rtrim(rtrim((string) $this->value, '0'), '.') . '%)'
            : $this->code . ' (-Rp ' . number_format((float) $this->value, 0, ',', '.') . ')';
    }
}
