<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(['RolesPermissionSeeder']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('owner');
        $this->category = ProductCategory::factory()->create();
    }

    public function test_admin_can_view_products(): void
    {
        Product::factory()->count(3)->create(['category_id' => $this->category->id]);

        $this->actingAs($this->admin)
            ->get(route('products.index'))
            ->assertOk();
    }

    public function test_create_product(): void
    {
        $data = [
            'name' => 'Dive Package',
            'category_id' => $this->category->id,
            'base_price' => 1500000,
            'unit' => 'pax',
            'stock_quantity' => 10,
        ];

        $this->actingAs($this->admin)
            ->post(route('products.store'), $data)
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['name' => 'Dive Package']);
    }

    public function test_update_product(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $this->actingAs($this->admin)
            ->put(route('products.update', $product), [
                'name' => 'Updated Product',
                'category_id' => $this->category->id,
                'base_price' => 2000000,
                'unit' => 'pax',
                'stock_quantity' => 5,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product',
        ]);
    }

    public function test_delete_product(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $this->actingAs($this->admin)
            ->delete(route('products.destroy', $product))
            ->assertRedirect();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_filter_by_category(): void
    {
        Product::factory()->create(['category_id' => $this->category->id]);
        $otherCategory = ProductCategory::factory()->create();
        Product::factory()->create(['category_id' => $otherCategory->id]);

        $this->actingAs($this->admin)
            ->get(route('products.index', ['category_id' => $this->category->id]))
            ->assertOk();
    }

    public function test_validation_requires_fields(): void
    {
        $this->actingAs($this->admin)
            ->post(route('products.store'), [
                'name' => '',
                'category_id' => '',
                'base_price' => '',
            ])
            ->assertSessionHasErrors(['name', 'category_id', 'base_price']);
    }

    public function test_user_without_permission_cannot_view(): void
    {
        $guide = User::factory()->create()->assignRole('dive-guide');

        $this->actingAs($guide)
            ->get(route('products.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_product_edit(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $this->actingAs($this->admin)
            ->get(route('products.edit', $product))
            ->assertOk();
    }

    public function test_toggle_product_active(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->put(route('products.update', $product), [
                'name' => $product->name,
                'category_id' => $this->category->id,
                'base_price' => $product->base_price,
                'unit' => $product->unit,
                'stock_quantity' => $product->stock_quantity,
                'is_active' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => false,
        ]);
    }
}
