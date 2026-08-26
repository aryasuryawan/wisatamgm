<?php

namespace App\Models;

use Database\Factories\EquipmentUnitFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentUnit extends Model
{
    /** @use HasFactory<EquipmentUnitFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'branch_id',
        'code',
        'condition',
        'status',
        'notes',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(EquipmentMaintenanceLog::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }
}
