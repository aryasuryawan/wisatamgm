<?php

namespace Tests\Feature\Discount;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Branch $branch;
    private ProductCategory $wisata;
    private ProductCategory $merch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(['RolesPermissionSeeder']);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('owner');

        $this->branch = Branch::factory()->create();
        $this->wisata = ProductCategory::factory()->create(['type_slug' => 'wisata']);
        $this->merch = ProductCategory::factory()->create(['type_slug' => 'merchandise']);

        $this->actingAs($this->owner);
    }

    public function test_crud_lifecycle(): void
    {
        $this->post(route('discounts.store'), [
            'code' => 'TEST10',
            'name' => 'Test Discount',
            'type' => 'percent',
            'value' => 10,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addMonth()->toDateString(),
        ])->assertRedirect();

        $discount = Discount::where('code', 'TEST10')->firstOrFail();

        $this->put(route('discounts.update', $discount), [
            'code' => 'TEST10',
            'name' => 'Renamed',
            'type' => 'nominal',
            'value' => 25000,
            'valid_from' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertEquals('Renamed', $discount->fresh()->name);

        $this->delete(route('discounts.destroy', $discount))->assertRedirect();
        $this->assertDatabaseMissing('discounts', ['id' => $discount->id]);
    }

    public function test_cannot_delete_used_discount(): void
    {
        $discount = Discount::factory()->create();
        $tx = \App\Models\Transaction::factory()->create(['branch_id' => $this->branch->id]);
        $discount->usages()->create([
            'transaction_id' => $tx->id,
            'amount' => 10000,
        ]);

        $this->delete(route('discounts.destroy', $discount))
            ->assertForbidden();

        $this->assertDatabaseHas('discounts', ['id' => $discount->id]);
    }

    public function test_percent_above_100_rejected(): void
    {
        $this->post(route('discounts.store'), [
            'code' => 'GILA150',
            'name' => 'Over 100',
            'type' => 'percent',
            'value' => 150,
        ])->assertSessionHasErrors(['value']);
    }

    public function test_code_must_be_unique(): void
    {
        Discount::factory()->create(['code' => 'UNIK']);

        $this->post(route('discounts.store'), [
            'code' => 'UNIK',
            'name' => 'Dup',
            'type' => 'nominal',
            'value' => 5000,
        ])->assertSessionHasErrors(['code']);
    }

    public function test_valid_until_before_from_rejected(): void
    {
        $this->post(route('discounts.store'), [
            'code' => 'BALIK',
            'name' => 'Backwards',
            'type' => 'nominal',
            'value' => 5000,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->subDay()->toDateString(),
        ])->assertSessionHasErrors(['valid_until']);
    }

    public function test_invalid_code_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service()->resolveAndCalculate('TIDAKADA', [], null, $this->branch->id);
    }

    public function test_expired_code_rejected(): void
    {
        $discount = Discount::factory()->expired()->create(['code' => 'EXPIRED']);

        $this->expectException(\InvalidArgumentException::class);

        $this->service()->resolveAndCalculate('EXPIRED', [], null, $this->branch->id);
    }

    public function test_inactive_code_rejected(): void
    {
        Discount::factory()->inactive()->create(['code' => 'MATI']);

        $this->expectException(\InvalidArgumentException::class);

        $this->service()->resolveAndCalculate('MATI', [], null, $this->branch->id);
    }

    public function test_branch_scoped_code_rejected_on_other_branch(): void
    {
        Discount::factory()->forBranch($this->branch)->create(['code' => 'CABANG']);

        $other = Branch::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        $this->service()->resolveAndCalculate('CABANG', [
            ['type_slug' => 'wisata', 'line_total' => '100000'],
        ], null, $other->id);
    }

    public function test_usage_limit_reached(): void
    {
        Discount::factory()->create(['code' => 'LIMITED', 'usage_limit' => 1]);
        $tx = \App\Models\Transaction::factory()->create(['branch_id' => $this->branch->id]);
        Discount::where('code', 'LIMITED')->first()->usages()->create([
            'transaction_id' => $tx->id,
            'amount' => 1000,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service()->resolveAndCalculate('LIMITED', [
            ['type_slug' => 'wisata', 'line_total' => '100000'],
        ], null, $this->branch->id);
    }

    public function test_per_customer_limit_reached(): void
    {
        Discount::factory()->create(['code' => 'PERCUST', 'usage_limit_per_customer' => 1]);
        $customer = Customer::factory()->create();
        $tx = \App\Models\Transaction::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
        ]);
        Discount::where('code', 'PERCUST')->first()->usages()->create([
            'transaction_id' => $tx->id,
            'customer_id' => $customer->id,
            'amount' => 1000,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service()->resolveAndCalculate('PERCUST', [
            ['type_slug' => 'wisata', 'line_total' => '100000'],
        ], $customer->id, $this->branch->id);
    }

    public function test_scope_mismatch_rejected(): void
    {
        Discount::factory()->create([
            'code' => 'MERCHONLY',
            'category_scope' => ['merchandise'],
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service()->resolveAndCalculate('MERCHONLY', [
            ['type_slug' => 'wisata', 'line_total' => '100000'],
        ], null, $this->branch->id);
    }

    public function test_nominal_capped_at_discountable(): void
    {
        Discount::factory()->nominal(100000)->create(['code' => 'GEDE']);

        $result = $this->service()->resolveAndCalculate('GEDE', [
            ['type_slug' => 'merchandise', 'line_total' => '40000'],
        ], null, $this->branch->id);

        $this->assertEquals('40000.00', $result['amount']);
    }

    public function test_percent_only_on_scoped_lines(): void
    {
        Discount::factory()->percent(10)->create([
            'code' => 'SCOPED',
            'category_scope' => ['wisata'],
        ]);

        $result = $this->service()->resolveAndCalculate('SCOPED', [
            ['type_slug' => 'wisata', 'line_total' => '1000000'],
            ['type_slug' => 'merchandise', 'line_total' => '200000'],
        ], null, $this->branch->id);

        // Hanya line wisata yang kena: 10% x 1.000.000.
        $this->assertEquals('100000.00', $result['amount']);
    }

    public function test_pos_applies_code_end_to_end(): void
    {
        Discount::factory()->percent(10)->create(['code' => 'POS10']);
        $product = Product::factory()->create([
            'category_id' => $this->wisata->id,
            'base_price' => 1000000,
        ]);
        $customer = Customer::factory()->create();

        $tx = app(\App\Domain\Transaction\Services\TransactionService::class)->create(
            [
                'branch_id' => $this->branch->id,
                'customer_id' => $customer->id,
                'discount_code' => 'POS10',
            ],
            [['product_id' => $product->id, 'qty' => 1]],
            [['method' => 'cash', 'amount' => 999000]],
        );

        // 1.000.000 - 10% = 900.000; PPN 99.000; total 999.000.
        $this->assertEquals('100000.00', (string) $tx->discount_total);
        $this->assertEquals('99000.00', (string) $tx->tax_total);
        $this->assertEquals('999000.00', (string) $tx->grand_total);
        $this->assertEquals('paid', $tx->status);

        $this->assertDatabaseHas('discount_usages', [
            'discount_id' => Discount::where('code', 'POS10')->value('id'),
            'transaction_id' => $tx->id,
            'amount' => '100000.00',
        ]);
    }

    public function test_marketing_can_create_but_not_delete(): void
    {
        $marketing = User::factory()->create();
        $marketing->assignRole('marketing');

        $this->actingAs($marketing)
            ->post(route('discounts.store'), [
                'code' => 'MKT10',
                'name' => 'Marketing Promo',
                'type' => 'percent',
                'value' => 10,
            ])
            ->assertRedirect();

        $discount = Discount::where('code', 'MKT10')->firstOrFail();

        $this->actingAs($marketing)
            ->delete(route('discounts.destroy', $discount))
            ->assertForbidden();
    }

    public function test_kasir_cannot_view_discount_admin(): void
    {
        $kasir = User::factory()->create();
        $kasir->assignRole('kasir');

        $this->actingAs($kasir)
            ->get(route('discounts.index'))
            ->assertForbidden();
    }

    public function test_discount_preview_endpoint_valid(): void
    {
        Discount::factory()->percent(10)->create(['code' => 'PREV10']);
        $product = Product::factory()->create([
            'category_id' => $this->wisata->id,
            'base_price' => 500000,
        ]);

        $this->post(route('transactions.discount.preview'), [
            'code' => 'PREV10',
            'branch_id' => $this->branch->id,
            'items' => [['product_id' => $product->id, 'qty' => 2]],
        ])->assertOk()
            ->assertJson(['valid' => true, 'amount' => '100000.00']);
    }

    public function test_discount_preview_endpoint_invalid_code(): void
    {
        $product = Product::factory()->create(['category_id' => $this->wisata->id]);

        $this->post(route('transactions.discount.preview'), [
            'code' => 'TIDAKADA',
            'branch_id' => $this->branch->id,
            'items' => [['product_id' => $product->id, 'qty' => 1]],
        ])->assertStatus(422)
            ->assertJson(['valid' => false]);
    }

    public function test_discount_preview_endpoint_scope_mismatch(): void
    {
        Discount::factory()->create([
            'code' => 'MERCHONLY2',
            'category_scope' => ['merchandise'],
        ]);
        $product = Product::factory()->create(['category_id' => $this->wisata->id]);

        $this->post(route('transactions.discount.preview'), [
            'code' => 'MERCHONLY2',
            'branch_id' => $this->branch->id,
            'items' => [['product_id' => $product->id, 'qty' => 1]],
        ])->assertStatus(422)
            ->assertJson(['valid' => false]);
    }

    private function service(): \App\Domain\Discount\Services\DiscountService
    {
        return app(\App\Domain\Discount\Services\DiscountService::class);
    }
}
