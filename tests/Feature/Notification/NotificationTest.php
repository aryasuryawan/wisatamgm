<?php

namespace Tests\Feature\Notification;

use App\Domain\Transaction\Services\TransactionService;
use App\Jobs\SendGmailInvoice;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EmailLog;
use App\Models\Product;
use App\Models\Schedule;
use App\Models\ScheduleParticipant;
use App\Models\User;
use App\Models\WhatsAppLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(['RolesPermissionSeeder', 'ExpenseCategorySeeder']);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('owner');
    }

    public function test_paid_transaction_queues_wa_and_email_invoice(): void
    {
        Queue::fake();

        $customer = Customer::factory()->create([
            'phone' => '+6281234567890',
            'email' => 'cust@example.com',
        ]);

        $service = app(TransactionService::class);
        $product = Product::factory()->create([
            'base_price' => 500000,
            'branch_id' => Branch::factory()->create()->id,
        ]);

        $this->actingAs($this->owner);

        // PPN 11% server-side: total = 555.000 → bayar penuh supaya status paid
        $transaction = $service->create(
            ['branch_id' => $product->branch_id, 'customer_id' => $customer->id],
            [['product_id' => $product->id, 'qty' => 1]],
            [['method' => 'cash', 'amount' => 555000]],
        );

        $this->assertEquals('paid', $transaction->status);

        $this->assertDatabaseHas('whatsapp_logs', [
            'customer_id' => $customer->id,
            'type' => 'invoice_paid',
            'phone' => '+6281234567890',
            'status' => 'queued',
        ]);

        $this->assertDatabaseHas('email_logs', [
            'customer_id' => $customer->id,
            'email' => 'cust@example.com',
            'status' => 'queued',
        ]);

        Queue::assertPushed(SendWhatsAppMessage::class);
        Queue::assertPushed(SendGmailInvoice::class);
    }

    public function test_fonnte_job_marks_failed_without_token(): void
    {
        config(['services.fonnte.token' => '']);
        $log = WhatsAppLog::factory()->create(['status' => 'queued']);

        (new SendWhatsAppMessage($log->id))->handle();

        $log->refresh();
        $this->assertEquals('failed', $log->status);
        $this->assertStringContainsString('Fonnte', (string) $log->error_message);
    }

    public function test_fonnte_job_sends_and_updates_log(): void
    {
        config(['services.fonnte.token' => 'test-token']);

        Http::fake([
            'api.fonnte.com/send' => Http::response(['status' => true, 'id' => 'ABC123']),
        ]);

        $log = WhatsAppLog::factory()->create(['status' => 'queued', 'phone' => '+62811111111']);

        (new SendWhatsAppMessage($log->id))->handle();

        $log->refresh();
        $this->assertEquals('sent', $log->status);
        $this->assertEquals('ABC123', $log->provider_ref);

        Http::assertSent(fn ($request) => $request['target'] === '+62811111111');
    }

    public function test_remind_command_creates_deduped_reminders(): void
    {
        // Jadwal H-1
        $schedule = Schedule::factory()->confirmed()->create([
            'date_start' => now()->addDay()->setHour(9),
            'date_end' => now()->addDay()->setHour(15),
        ]);
        $participant = ScheduleParticipant::factory()->create(['schedule_id' => $schedule->id]);
        $participant->customer->update(['phone' => '+628999888777']);

        $this->artisan('schedule:remind')->assertSuccessful();

        $count = WhatsAppLog::where('type', 'schedule_reminder_H-1')
            ->where('schedule_id', $schedule->id)
            ->count();
        $this->assertEquals(1, $count);

        // Jalankan lagi → tidak duplikat
        $this->artisan('schedule:remind')->assertSuccessful();
        $this->assertEquals(1, $count);
    }

    public function test_notifications_index_access(): void
    {
        WhatsAppLog::factory()->create();
        EmailLog::factory()->create();

        $finance = User::factory()->create();
        $finance->assignRole('finance');

        $this->actingAs($finance)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee(WhatsAppLog::first()->phone);

        $kasir = User::factory()->create();
        $kasir->assignRole('kasir');

        $this->actingAs($kasir)
            ->get(route('notifications.index'))
            ->assertForbidden();
    }
}
