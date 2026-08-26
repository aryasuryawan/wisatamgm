<?php

namespace Tests\Feature\Finance;

use App\Domain\Booking\Services\BookingService;
use App\Domain\Transaction\Services\TransactionService;
use App\Models\BookableUnit;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProofUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesPermissionSeeder::class, 'ExpenseCategorySeeder']);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('owner');

        Storage::fake('public');
    }

    public function test_expense_proof_upload_stores_file(): void
    {
        $branch = Branch::factory()->create();

        $this->actingAs($this->owner)
            ->post(route('expenses.store'), [
                'branch_id' => $branch->id,
                'expense_category_id' => 1,
                'description' => 'Servis boat + kuitansi',
                'amount' => 450000,
                'expense_date' => now()->toDateString(),
                'proof' => UploadedFile::fake()->create('kuitansi.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('expenses.index'));

        $expense = Expense::where('description', 'Servis boat + kuitansi')->firstOrFail();

        $this->assertStringStartsWith('proofs/', $expense->proof_path);
        Storage::disk('public')->assertExists($expense->proof_path);
    }

    public function test_expense_proof_invalid_type_rejected(): void
    {
        $branch = Branch::factory()->create();

        $this->actingAs($this->owner)
            ->post(route('expenses.store'), [
                'branch_id' => $branch->id,
                'expense_category_id' => 1,
                'description' => 'File exe',
                'amount' => 1000,
                'expense_date' => now()->toDateString(),
                'proof' => UploadedFile::fake()->create('virus.exe', 10),
            ])
            ->assertSessionHasErrors('proof');
    }

    public function test_payment_proof_upload_stores_file(): void
    {
        $service = app(TransactionService::class);

        $branch = Branch::factory()->create();
        $product = Product::factory()->create(['branch_id' => $branch->id, 'base_price' => 200000]);

        $this->actingAs($this->owner);
        $txn = $service->create(
            ['branch_id' => $branch->id],
            [['product_id' => $product->id, 'qty' => 1]],
            []
        );

        $this->actingAs($this->owner)
            ->post(route('transactions.payments.store', $txn), [
                'method' => 'transfer',
                'amount' => 222000, // full incl PPN
                'proof' => UploadedFile::fake()->image('bukti-transfer.jpg'),
            ])
            ->assertRedirect();

        $payment = $txn->payments()->firstOrFail();
        $this->assertStringStartsWith('proofs/', (string) $payment->proof_path);
        Storage::disk('public')->assertExists($payment->proof_path);
    }

    public function test_booking_payment_with_proof(): void
    {
        $bookingService = app(BookingService::class);
        $branch = Branch::factory()->create();
        $product = Product::factory()->create(['branch_id' => $branch->id]);
        $unit = BookableUnit::factory()->room()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
        ]);

        $booking = $bookingService->create($this->owner, [
            'bookable_unit_id' => $unit->id,
            'guest_name' => 'Tamu Bukti',
            'guests_count' => 2,
            'date_start' => '2026-10-01',
            'date_end' => '2026-10-03',
            'amount_total' => 900000,
        ]);

        $this->actingAs($this->owner)
            ->post(route('bookings.payments.store', $booking), [
                'method' => 'qris',
                'amount' => 499500, // DP separuh incl PPN
                'proof' => UploadedFile::fake()->image('qris.jpg'),
            ])
            ->assertRedirect();

        $booking->refresh();
        $payment = $booking->transaction->payments()->firstOrFail();
        Storage::disk('public')->assertExists($payment->proof_path);
    }
}
