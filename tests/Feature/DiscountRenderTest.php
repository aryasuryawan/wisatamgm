<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_discount_create_renders(): void
    {
        $this->seed(['RolesPermissionSeeder']);
        Branch::factory()->create();
        ProductCategory::factory()->create();
        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $r = $this->actingAs($owner)->get(route('discounts.create'));
        if ($e = $r->exception ?? null) {
            dump(class_basename($e), $e->getMessage(), $e->getFile() . ':' . $e->getLine());
        }
        $r->assertOk();
    }

    public function test_pos_store_with_code_works(): void
    {
        $this->seed(['RolesPermissionSeeder']);
        $branch = Branch::factory()->create();
        $cat = ProductCategory::factory()->create(['type_slug' => 'wisata']);
        $product = \App\Models\Product::factory()->create([
            'category_id' => $cat->id,
            'base_price' => 200000,
            'branch_id' => $branch->id,
        ]);
        \App\Models\Discount::factory()->percent(10)->create(['code' => 'POSHEMAT']);

        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $owner->branches()->attach($branch->id);

        $r = $this->actingAs($owner)->post(route('transactions.store'), [
            'branch_id' => $branch->id,
            'discount_code' => 'POSHEMAT',
            'idempotency_key' => 'feature-code-1',
            'items_json' => json_encode([['product_id' => $product->id, 'qty' => 1]]),
            'payments_json' => json_encode([['method' => 'cash', 'amount' => 199800]]),
        ]);

        if ($e = $r->exception ?? null) {
            dump(class_basename($e), $e->getMessage());
        }
        $r->assertRedirect();

        $this->assertDatabaseHas('transactions', [
            'discount_total' => '20000.00',
            'grand_total' => '199800.00',
        ]);
    }
}
