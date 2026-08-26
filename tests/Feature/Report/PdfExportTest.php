<?php

namespace Tests\Feature\Report;

use App\Domain\Transaction\Services\TransactionService;
use App\Jobs\SendGmailInvoice;
use App\Mail\InvoiceMail;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EmailLog;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PdfExportTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesPermissionSeeder::class, 'ExpenseCategorySeeder']);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('owner');
    }

    private function paidTransaction(): Transaction
    {
        $branch = Branch::factory()->create();
        $product = Product::factory()->create(['branch_id' => $branch->id, 'base_price' => 400000]);

        $this->actingAs($this->owner);

        return app(TransactionService::class)->create(
            ['branch_id' => $branch->id, 'customer_id' => null],
            [['product_id' => $product->id, 'qty' => 1]],
            [['method' => 'cash', 'amount' => 444000]],
        );
    }

    public function test_transaction_pdf_downloads(): void
    {
        $txn = $this->paidTransaction();

        $response = $this->actingAs($this->owner)
            ->get(route('transactions.pdf', $txn))
            ->assertOk();

        $this->assertStringStartsWith('%PDF', substr($response->getContent(), 0, 5));
    }

    public function test_report_pdf_downloads(): void
    {
        $this->paidTransaction();

        $this->actingAs($this->owner)
            ->get(route('reports.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_invoice_email_has_pdf_attachment(): void
    {
        Mail::fake();
        config(['mail.default' => 'log']);

        $customer = Customer::factory()->create(['email' => 'inv@example.com']);
        $txn = $this->paidTransaction();
        $txn->customer_id = $customer->id;
        $txn->save();

        $log = EmailLog::create([
            'customer_id' => $customer->id,
            'transaction_id' => $txn->id,
            'email' => $customer->email,
            'subject' => 'Invoice #'.$txn->id,
            'status' => 'queued',
        ]);

        (new SendGmailInvoice($log->id, [
            'transaction_no' => $txn->id,
            'grand_total' => (float) $txn->grand_total,
            'paid_total' => 444000,
            'customer_name' => $customer->name,
        ]))->handle();

        Mail::assertQueued(InvoiceMail::class, function ($mail) {
            return count($mail->rawAttachments) === 1
                && str_contains($mail->rawAttachments[0]['data'], '%PDF');
        });

        $this->assertEquals('sent', $log->fresh()->status);
    }

    public function test_kasir_cannot_access_report_pdf_but_can_transaction_pdf(): void
    {
        $kasir = User::factory()->create();
        $kasir->assignRole('kasir');

        $this->actingAs($kasir)->get(route('reports.pdf'))->assertForbidden();

        $txn = $this->paidTransaction();
        // Kasir hanya boleh melihat transaksi di cabangnya.
        $kasir->branches()->attach($txn->branch_id);

        $this->actingAs($kasir)->get(route('transactions.pdf', $txn))->assertOk();
    }
}
