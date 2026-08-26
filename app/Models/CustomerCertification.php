<?php

namespace App\Models;

use Database\Factories\CustomerCertificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerCertification extends Model
{
    /** @use HasFactory<CustomerCertificationFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'agency',
        'level',
        'cert_number',
        'cert_date',
        'expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'cert_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
