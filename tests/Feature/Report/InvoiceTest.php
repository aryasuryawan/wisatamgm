<?php

namespace Tests\Feature\Report;

use App\Domain\Booking\Services\BookingService;
use App\Jobs\SendGmailInvoice;
use App\Models\BookableUnit;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesPermissionSeeder::class]);

        $this->branch = Branch::factory()->create();
        $this->owner = User::factory()->create();
        $this->owner->assignRole('owner');
        $this->actingAs($this->owner);
    }

    private function bookingWithCustomer(): Booking
    {
        $product = Product::factory()->create(['branch_id' => $this->branch->id, 'base_price' => 500000]);
        $unit = BookableUnit::factory()->room()->create([
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
        ]);
        $customer = Customer::factory()->create([
            'email' => 'corp@example.com',
            'branch_id' => $this->branch->id,
        ]);

        return app(BookingService::class)->create($this->owner, [
            'bookable_unit_id' => $unit->id,
            'customer_id' => $customer->id,
            'guest_name' => 'PT Contoh',
            'guests_count' => 2,
            'date_start' => '2026-11-10',
            'date_end' => '2026-11-12',
            'amount_total' => 1000000,
        ]);
    }

    public function test_issue_invoice_creates_unpaid_transaction_linked_to_booking(): void
    {
        $booking = $this->bookingWithCustomer();

        $this->post(route('bookings.invoice', $booking))
            ->assertRedirect();

        $booking->refresh();
        $this->assertNotNull($booking->transaction_id);

        $txn = $booking->transaction;
        $this->assertEquals('confirmed', $txn->status);
        $this->assertEquals(0, $txn->payments()->count());
        $this->assertEquals('2026-11-10 12:00', $txn->transaction_date->format('Y-m-d H:i'));
    }

    public function test_issue_invoice_twice_rejected(): void
    {
        $booking = $this->bookingWithCustomer();

        $this->post(route('bookings.invoice', $booking))->assertRedirect();
        $this->followingRedirects()
            ->post(route('bookings.invoice', $booking))
            ->assertOk()
            ->assertSee('sudah ada');
    }

    public function test_outstanding_invoice_appears_on_invoices_page(): void
    {
        $booking = $this->bookingWithCustomer();
        $this->post(route('bookings.invoice', $booking));

        $response = $this->get(route('transactions.invoices'))->assertOk();
        $html = (string) $response->getContent();

        $this->assertStringContainsString('invoices-table', $html);
        // Grand total incl PPN 1.110.000 → tampil sebagai sisa.
        $this->assertStringContainsString('Rp 1.110.000', $html);
    }

    public function test_send_invoice_email_queues_job(): void
    {
        Queue::fake();

        $booking = $this->bookingWithCustomer();
        $this->post(route('bookings.invoice', $booking));

        $txn = $booking->fresh()->transaction;
        $this->post(route('transactions.send-invoice', $txn))->assertRedirect();

        Queue::assertPushed(SendGmailInvoice::class);
    }

    public function test_paid_transactions_do_not_appear_as_invoices(): void
    {
        $booking = $this->bookingWithCustomer();
        $this->post(route('bookings.invoice', $booking));

        $txn = $booking->fresh()->transaction;
        app(BookingService::class)
            ->recordPayment($txn ? $booking : $booking, $this->owner, 'transfer', 1110000);

        $this->get(route('transactions.invoices'))
            ->assertOk()
            ->assertSee('Tidak ada tagihan outstanding');
    }
}
