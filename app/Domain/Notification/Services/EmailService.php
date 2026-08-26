<?php

namespace App\Domain\Notification\Services;

use App\Jobs\SendGmailInvoice;
use App\Models\Customer;
use App\Models\EmailLog;
use App\Models\Transaction;

/**
 * Entri point email. Log dibuat dulu (status queued), lalu job queue kirim
 * via mailer aktif (.env MAIL_MAILER). Transport Gmail API resmi menunggu
 * credentials OAuth — lihat catatan deviasi di progress tracker.
 */
class EmailService
{
    public function queueInvoice(
        Customer $customer,
        Transaction $transaction,
        string $subject,
        array $invoiceData,
    ): EmailLog {
        /** @var EmailLog $log */
        $log = EmailLog::create([
            'customer_id' => $customer->id,
            'transaction_id' => $transaction->id,
            'email' => $customer->email,
            'subject' => $subject,
            'status' => EmailLog::STATUSES[0],
        ]);

        SendGmailInvoice::dispatch($log->id, $invoiceData);

        return $log;
    }
}
