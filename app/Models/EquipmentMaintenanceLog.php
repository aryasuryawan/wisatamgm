<?php

namespace App\Models;

use Database\Factories\EquipmentMaintenanceLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentMaintenanceLog extends Model
{
    /** @use HasFactory<EquipmentMaintenanceLogFactory> */
    use HasFactory;

    protected $fillable = [
        'equipment_unit_id',
        'date',
        'type',
        'description',
        'cost',
        'performed_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'cost' => 'decimal:2',
        ];
    }

    public function equipmentUnit(): BelongsTo
    {
        return $this->belongsTo(EquipmentUnit::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
