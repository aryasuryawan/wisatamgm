<?php

namespace App\Models;

use Database\Factories\WhatsAppLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppLog extends Model
{
    /** @use HasFactory<WhatsAppLogFactory> */
    use HasFactory;

    public const STATUSES = ['queued', 'sent', 'failed'];

    public const TYPES = [
        'manual' => 'manual',
        'transaction_confirmed' => 'transaction_confirmed',
        'invoice_paid' => 'invoice_paid',
        'schedule_reminder' => 'schedule_reminder',
    ];

    protected $table = 'whatsapp_logs';

    protected $fillable = [
        'customer_id',
        'transaction_id',
        'schedule_id',
        'phone',
        'type',
        'message',
        'status',
        'provider_ref',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }
}
