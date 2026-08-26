<?php

namespace App\Domain\Notification\Services;

use App\Jobs\SendWhatsAppMessage;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\WhatsAppLog;

/**
 * Entri point pengiriman WhatsApp. Semua kirim WA lewat sini supaya
 * konsisten: bikin baris log `queued` dulu, lalu job queue yang benar-benar
 * memanggil provider (Fonnte) dan meng-update statusnya.
 */
class WhatsAppService
{
    public function queue(
        string $phone,
        string $message,
        string $type,
        ?Customer $customer = null,
        ?Transaction $transaction = null,
        ?int $scheduleId = null,
    ): WhatsAppLog {
        /** @var WhatsAppLog $log */
        $log = WhatsAppLog::create([
            'customer_id' => $customer?->id,
            'transaction_id' => $transaction?->id,
            'schedule_id' => $scheduleId,
            'phone' => $phone,
            'type' => $type,
            'message' => $message,
            'status' => WhatsAppLog::STATUSES[0],
        ]);

        SendWhatsAppMessage::dispatch($log->id);

        return $log;
    }
}
