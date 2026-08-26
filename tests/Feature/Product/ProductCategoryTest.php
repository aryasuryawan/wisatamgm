<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(['RolesPermissionSeeder']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('owner');
    }

    public function test_admin_can_view_categories(): void
    {
        ProductCategory::factory()->count(3)->create();

        $this->actingAs($this->admin)
            ->get(route('product-categories.index'))
            ->assertOk();
    }

    public function test_create_category(): void
    {
        $data = [
            'name' => 'Snorkeling Trip',
            'type_slug' => 'snorkeling',
            'sort_order' => 1,
        ];

        $this->actingAs($this->admin)
            ->post(route('product-categories.store'), $data)
            ->assertRedirect();

        $this->assertDatabaseHas('product_categories', ['type_slug' => 'snorkeling']);
    }

    public function test_update_category(): void
    {
        $category = ProductCategory::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('product-categories.update', $category), [
                'name' => 'Updated Name',
                'type_slug' => $category->type_slug,
                'sort_order' => 5,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('product_categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_delete_category(): void
    {
        $category = ProductCategory::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('product-categories.destroy', $category))
            ->assertRedirect();

        $this->assertDatabaseMissing('product_categories', ['id' => $category->id]);
    }

    public function test_delete_category_with_products_fails(): void
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $this->actingAs($this->admin)
            ->delete(route('product-categories.destroy', $category))
            ->assertRedirect();

        $this->assertDatabaseHas('product_categories', ['id' => $category->id]);
    }

    public function test_validation_requires_name(): void
    {
        $this->actingAs($this->admin)
            ->post(route('product-categories.store'), ['name' => '', 'type_slug' => '', 'sort_order' => ''])
            ->assertSessionHasErrors(['name', 'type_slug']);
    }

    public function test_type_slug_must_be_unique(): void
    {
        ProductCategory::factory()->create(['type_slug' => 'dive']);

        $this->actingAs($this->admin)
            ->post(route('product-categories.store'), [
                'name' => 'Another',
                'type_slug' => 'dive',
                'sort_order' => 0,
            ])
            ->assertSessionHasErrors(['type_slug']);
    }

    public function test_user_without_permission_cannot_view(): void
    {
        $finance = User::factory()->create()->assignRole('finance');

        $this->actingAs($finance)
            ->get(route('product-categories.index'))
            ->assertForbidden();
    }
}
