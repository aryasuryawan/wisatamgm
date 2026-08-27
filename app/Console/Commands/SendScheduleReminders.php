<?php

namespace App\Console\Commands;

use App\Domain\Notification\Services\WhatsAppService;
use App\Models\Schedule;
use App\Models\WhatsAppLog;
use Illuminate\Console\Command;

class SendScheduleReminders extends Command
{
    protected $signature = 'schedule:remind {--dry-run : Tampilkan pesan tanpa mengirim}';

    protected $description = 'Kirim reminder WhatsApp H-3 dan H-1 ke peserta jadwal terkonfirmasi';

    public function handle(WhatsAppService $whatsapp): int
    {
        $targets = [
            ['days' => 3, 'label' => 'H-3'],
            ['days' => 1, 'label' => 'H-1'],
        ];

        $sent = 0;

        foreach ($targets as $target) {
            // Jadwal yang date_start-nya tepat N hari ke depan (bandingkan tanggal saja).
            $day = now()->copy()->addDays($target['days']);

            $schedules = Schedule::query()
                ->where('status', 'confirmed')
                ->whereBetween('date_start', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
                ->with(['participants.customer', 'product'])
                ->get();

            foreach ($schedules as $schedule) {
                foreach ($schedule->participants as $participant) {
                    $customer = $participant->customer;

                    if (! $customer || ! $customer->phone) {
                        continue;
                    }

                    // Dedupe: jangan kirim reminder tipe yang sama dua kali untuk peserta yang sama.
                    $exists = WhatsAppLog::query()
                        ->where('type', 'schedule_reminder_'.$target['label'])
                        ->where('schedule_id', $schedule->id)
                        ->where('customer_id', $customer->id)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $template = \App\Models\Setting::get('wa_schedule_reminder', __('messages.wa_schedule_reminder'));
                    $message = str_replace(
                        [':label', ':name', ':product', ':date'],
                        [
                            $target['label'],
                            $customer->name,
                            $schedule->product?->name ?? '-',
                            optional($schedule->date_start)->format('d M Y H:i'),
                        ],
                        $template
                    );

                    if ($this->option('dry-run')) {
                        $this->line("[DRY] {$customer->phone}: {$message}");
                        $sent++;

                        continue;
                    }

                    $whatsapp->queue(
                        phone: $customer->phone,
                        message: $message,
                        type: 'schedule_reminder_'.$target['label'],
                        customer: $customer,
                        scheduleId: $schedule->id,
                    );

                    $sent++;
                }
            }
        }

        $this->info("Reminder queued: {$sent}");

        return self::SUCCESS;
    }
}
