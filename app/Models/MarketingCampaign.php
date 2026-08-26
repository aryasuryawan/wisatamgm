<?php

namespace App\Models;

use Database\Factories\MarketingCampaignFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingCampaign extends Model
{
    /** @use HasFactory<MarketingCampaignFactory> */
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'channel',
        'budget',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'marketing_campaign_id');
    }

    public function totalSpent(): float
    {
        return (float) $this->expenses()->sum('amount');
    }

    public function scopeForBranches(Builder $query, array $branchIds): Builder
    {
        return $query->where(function (Builder $q) use ($branchIds) {
            $q->whereIn('branch_id', $branchIds)->orWhereNull('branch_id');
        });
    }
}
