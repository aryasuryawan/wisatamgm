<?php

namespace App\Jobs;

use App\Models\WhatsAppLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(private int $whatsappLogId) {}

    public function handle(): void
    {
        $log = WhatsAppLog::find($this->whatsappLogId);

        if (! $log || $log->status === 'sent') {
            return;
        }

        $token = (string) config('services.fonnte.token');

        if ($token === '') {
            $log->update([
                'status' => 'failed',
                'error_message' => 'Fonnte token belum dikonfigurasi (services.fonnte.token)',
            ]);

            return;
        }

        try {
            $response = Http::withToken($token)
                ->asForm()
                ->timeout(15)
                ->post((string) config('services.fonnte.url', 'https://api.fonnte.com/send'), [
                    'target' => $log->phone,
                    'message' => $log->message,
                ]);

            $body = $response->json();

            if ($response->successful() && (($body['status'] ?? false) === true)) {
                $log->update([
                    'status' => 'sent',
                    'provider_ref' => (string) ($body['id'] ?? ''),
                    'sent_at' => now(),
                ]);
            } else {
                $log->update([
                    'status' => 'failed',
                    'error_message' => mb_substr($response->body(), 0, 500),
                ]);
            }
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => mb_substr($e->getMessage(), 0, 500),
            ]);

            Log::warning('Fonnte send failed', ['log_id' => $log->id, 'error' => $e->getMessage()]);
        }
    }
}
