<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionItem extends Model
{
    protected $fillable = [
        'transaction_id',
        'product_id',
        'qty',
        'price',
        'schedule_id',
        'equipment_unit_id',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function equipmentUnit(): BelongsTo
    {
        return $this->belongsTo(EquipmentUnit::class);
    }

    public function lineTotal(): string
    {
        return bcmul((string) $this->price, (string) $this->qty, 2);
    }
}
