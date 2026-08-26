<?php

namespace Tests\Feature\Transaction;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Schedule;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $kasir;
    private Branch $branch;
    private ProductCategory $merch;
    private ProductCategory $wisata;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(['RolesPermissionSeeder']);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('owner');

        $this->kasir = User::factory()->create();
        $this->kasir->assignRole('kasir');

        $this->branch = Branch::factory()->create();
        $this->kasir->branches()->attach($this->branch->id);

        $this->actingAs($this->kasir);

        $this->merch = ProductCategory::factory()->create(['type_slug' => 'merchandise']);
        $this->wisata = ProductCategory::factory()->create(['type_slug' => 'wisata']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'branch_id' => $this->branch->id,
            'customer_id' => null,
            'discount_total' => 0,
        ], $overrides);
    }

    public function test_create_computes_totals_with_ppn(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->wisata->id,
            'base_price' => 1_000_000,
        ]);

        $tx = app(\App\Domain\Transaction\Services\TransactionService::class)->create(
            $this->payload(['idempotency_key' => 'key-1']),
            [['product_id' => $product->id, 'qty' => 2]],
            [['method' => 'cash', 'amount' => 2_220_000]],
        );

        $this->assertEquals('2000000.00', (string) $tx->subtotal);
        $this->assertEquals('220000.00', (string) $tx->tax_total);
        $this->assertEquals('2220000.00', (string) $tx->grand_total);
        $this->assertEquals('paid', $tx->status);
    }

    public function test_client_price_is_ignored(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->wisata->id,
            'base_price' => 500_000,
        ]);

        // Client mencoba mengirim harga 1 per item — harus diabaikan.
        $tx = app(\App\Domain\Transaction\Services\TransactionService::class)->create(
            $this->payload(),
            [['product_id' => $product->id, 'qty' => 1, 'price' => 1]],
        );

        $this->assertEquals('500000.00', (string) $tx->subtotal);
    }

    public function test_stock_out_for_merchandise(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->merch->id,
            'base_price' => 100_000,
            'stock_quantity' => 10,
        ]);

        $tx = app(\App\Domain\Transaction\Services\TransactionService::class)->create(
            $this->payload(),
            [['product_id' => $product->id, 'qty' => 3]],
        );

        $this->assertEquals(7, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'out',
            'qty' => -3,
            'ref_type' => 'transaction',
            'ref_id' => $tx->id,
        ]);
    }

    public function test_no_stock_out_for_wisata(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->wisata->id,
            'base_price' => 100_000,
            'stock_quantity' => 10,
        ]);

        app(\App\Domain\Transaction\Services\TransactionService::class)->create(
            $this->payload(),
            [['product_id' => $product->id, 'qty' => 2]],
        );

        $this->assertEquals(10, $product->fresh()->stock_quantity);
    }

    public function test_insufficient_stock_blocks_transaction(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->merch->id,
            'base_price' => 100_000,
            'stock_quantity' => 1,
        ]);

        $this->expectException(\RuntimeException::class);

        app(\App\Domain\Transaction\Services\TransactionService::class)->create(
            $this->payload(),
            [['product_id' => $product->id, 'qty' => 5]],
        );

        $this->assertEquals(1, $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_schedule_participant_linked(): void
    {
        $customer = Customer::factory()->create();
        $schedule = Schedule::factory()->create([
            'branch_id' => $this->branch->id,
            'capacity' => 4,
        ]);
        $product = Product::factory()->create([
            'category_id' => $this->wisata->id,
            'base_price' => 500_000,
        ]);

        $tx = app(\App\Domain\Transaction\Services\TransactionService::class)->create(
            $this->payload(['customer_id' => $customer->id]),
            [['product_id' => $product->id, 'qty' => 1, 'schedule_id' => $schedule->id]],
        );

        $item = $tx->items()->first();

        $this->assertDatabaseHas('schedule_participants', [
            'schedule_id' => $schedule->id,
            'customer_id' => $customer->id,
            'transaction_item_id' => $item->id,
        ]);
    }

    public function test_split_payment_partial_then_paid(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->wisata->id,
            'base_price' => 1_000_000,
        ]);
        $customer = Customer::factory()->create();

        $service = app(\App\Domain\Transaction\Services\TransactionService::class);
        $tx = $service->create(
            $this->payload(['customer_id' => $customer->id]),
            [['product_id' => $product->id, 'qty' => 1]],
            [['method' => 'cash', 'amount' => 500_000]],
        );

        $this->assertEquals('partial', $tx->status);
        $this->assertEquals(0, $customer->fresh()->total_orders);

        $service->addPayment($tx, 'qris', '610000');

        $this->assertEquals('paid', $tx->fresh()->status);
        $this->assertEquals(1, $customer->fresh()->total_orders);
        $this->assertEquals('1110000.00', (string) $customer->fresh()->total_spent);
    }

    public function test_overpay_blocked(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->wisata->id,
            'base_price' => 100_000,
        ]);

        $service = app(\App\Domain\Transaction\Services\TransactionService::class);
        $tx = $service->create($this->payload(), [['product_id' => $product->id, 'qty' => 1]]);

        $this->expectException(\InvalidArgumentException::class);
        $service->addPayment($tx, 'cash', '999999999');
    }

    public function test_idempotency_key_returns_existing(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->wisata->id,
            'base_price' => 100_000,
        ]);

        $service = app(\App\Domain\Transaction\Services\TransactionService::class);
        $first = $service->create(
            $this->payload(['idempotency_key' => 'same-key']),
            [['product_id' => $product->id, 'qty' => 1]],
        );
        $second = $service->create(
            $this->payload(['idempotency_key' => 'same-key']),
            [['product_id' => $product->id, 'qty' => 1]],
        );

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_void_restores_stock_and_counters(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->merch->id,
            'base_price' => 100_000,
            'stock_quantity' => 10,
        ]);
        $customer = Customer::factory()->create();

        $service = app(\App\Domain\Transaction\Services\TransactionService::class);
        $tx = $service->create(
            $this->payload(['customer_id' => $customer->id]),
            [['product_id' => $product->id, 'qty' => 4]],
            [['method' => 'cash', 'amount' => 444_000]],
        );

        $this->assertEquals(6, $product->fresh()->stock_quantity);
        $this->assertEquals(1, $customer->fresh()->total_orders);

        $service->void($tx);

        $this->assertEquals('void', $tx->fresh()->status);
        $this->assertEquals(10, $product->fresh()->stock_quantity);
        $this->assertEquals(0, $customer->fresh()->total_orders);
        $this->assertEquals('0.00', (string) $customer->fresh()->total_spent);
        $this->assertDatabaseHas('audit_logs', ['action' => 'transaction.void']);
    }

    public function test_kasir_cannot_void_via_http(): void
    {
        $tx = \App\Models\Transaction::factory()->create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->kasir->id,
        ]);

        $this->actingAs($this->kasir)
            ->post(route('transactions.void', $tx))
            ->assertForbidden();
    }

    public function test_owner_can_void_via_http(): void
    {
        $tx = \App\Models\Transaction::factory()->create(['branch_id' => $this->branch->id]);

        $this->actingAs($this->owner)
            ->post(route('transactions.void', $tx))
            ->assertRedirect();

        $this->assertEquals('void', $tx->fresh()->status);
    }

    public function test_kasir_can_create_via_http(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->wisata->id,
            'base_price' => 100_000,
        ]);

        $this->actingAs($this->kasir)
            ->post(route('transactions.store'), $this->payload([
                'items' => [['product_id' => $product->id, 'qty' => 1]],
                'payments' => [['method' => 'cash', 'amount' => 111_000]],
                'idempotency_key' => 'http-key-1',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('transactions', ['idempotency_key' => 'http-key-1', 'status' => 'paid']);
    }

    public function test_admin_cabang_sees_only_own_branch(): void
    {
        $otherBranch = Branch::factory()->create();
        Transaction::factory()->create(['branch_id' => $this->branch->id]);
        Transaction::factory()->create(['branch_id' => $otherBranch->id]);

        $admin = User::factory()->create();
        $admin->assignRole('admin-cabang');
        $admin->branches()->attach($this->branch->id);

        $response = $this->actingAs($admin)->get(route('transactions.index'));
        $response->assertOk();

        $this->assertEquals(1, $response->viewData('transactions')->count());
    }

    public function test_validation_rejects_empty_items(): void
    {
        $this->actingAs($this->kasir)
            ->post(route('transactions.store'), $this->payload(['items' => []]))
            ->assertSessionHasErrors(['items']);
    }
}
