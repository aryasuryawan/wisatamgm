<?php

namespace Tests\Feature\Inventory;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesPermissionSeeder::class);

        $this->finance = User::factory()->create();
        $this->finance->assignRole('finance');
    }

    public function test_finance_can_view_stock_movements(): void
    {
        $this->actingAs($this->finance)
            ->get(route('inventory.index'))
            ->assertOk();
    }

    public function test_stock_in_increases_quantity(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $this->actingAs($this->finance)
            ->post(route('inventory.store'), [
                'product_id' => $product->id,
                'type' => 'in',
                'qty' => 5,
                'notes' => 'Restock',
            ])
            ->assertRedirect();

        $product->refresh();
        $this->assertEquals(15, $product->stock_quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'in',
            'qty' => 5,
            'qty_after' => 15,
        ]);
    }

    public function test_adjust_sets_exact_quantity(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $this->actingAs($this->finance)
            ->post(route('inventory.store'), [
                'product_id' => $product->id,
                'type' => 'adjustment',
                'qty' => 8,
                'notes' => 'Stok opname',
            ])
            ->assertRedirect();

        $product->refresh();
        $this->assertEquals(8, $product->stock_quantity);
    }
}
