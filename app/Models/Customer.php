<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'phone',
        'email',
        'nationality_type',
        'customer_type',
        'source',
        'segment_tag',
        'notes',
        'preferences',
        'total_orders',
        'total_spent',
    ];

    protected function casts(): array
    {
        return [
            'total_orders' => 'integer',
            'total_spent' => 'decimal:2',
            'preferences' => 'array',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(CustomerCertification::class);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%");
        });
    }

    public function scopeOfBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeNationality(Builder $query, string $type): Builder
    {
        return $query->where('nationality_type', $type);
    }

    public function getSegmentAttribute(): string
    {
        if ($this->total_orders >= 5) {
            return 'VIP';
        }

        return $this->total_orders > 1 ? 'Repeat' : 'Baru';
    }

    public function getPreference(string $key, mixed $default = null): mixed
    {
        return data_get($this->preferences, $key, $default);
    }

    public function setPreference(string $key, mixed $value): void
    {
        $preferences = $this->preferences ?? [];
        data_set($preferences, $key, $value);
        $this->preferences = $preferences;
    }
}
