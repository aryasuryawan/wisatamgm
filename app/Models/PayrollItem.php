<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_period_id',
        'user_id',
        'base_salary',
        'trips_count',
        'pax_count',
        'commission_total',
        'deduction',
        'net_total',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'commission_total' => 'decimal:2',
            'deduction' => 'decimal:2',
            'net_total' => 'decimal:2',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recalculateNet(): void
    {
        $this->net_total = (float) $this->base_salary
            + (float) $this->commission_total
            - (float) $this->deduction;
    }
}
