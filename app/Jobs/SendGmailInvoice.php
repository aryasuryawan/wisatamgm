<?php

namespace App\Jobs;

use App\Mail\InvoiceMail;
use App\Models\EmailLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendGmailInvoice implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(private int $emailLogId, private array $invoiceData = []) {}

    public function handle(): void
    {
        $log = EmailLog::find($this->emailLogId);

        if (! $log || $log->status === 'sent') {
            return;
        }

        if ($log->email === '' || $log->email === null) {
            $log->update([
                'status' => 'failed',
                'error_message' => 'Pelanggan tidak punya alamat email.',
            ]);

            return;
        }

        try {
            $mailable = new InvoiceMail($log->subject, $this->invoiceData);

            // Lampirkan PDF invoice bila email terkait transaksi.
            if ($log->transaction) {
                $transaction = $log->transaction->load(['items.product', 'payments', 'customer', 'cashier', 'branch']);

                $pdf = Pdf::loadView('pdf.receipt', [
                    'transaction' => $transaction,
                    'remaining' => max(0, bcsub((string) $transaction->grand_total, $transaction->paidTotal(), 2)),
                ])->setPaper('a5')->output();

                $mailable->attachData($pdf, 'invoice-'.$transaction->id.'.pdf', [
                    'mime' => 'application/pdf',
                ]);
            }

            Mail::to($log->email)->send($mailable);

            $log->update([
                'status' => 'sent',
                'provider_ref' => config('mail.mailers.smtp.username') ?? config('mail.default'),
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => mb_substr($e->getMessage(), 0, 500),
            ]);

            Log::warning('Invoice email failed', ['log_id' => $log->id, 'error' => $e->getMessage()]);
        }
    }
}
