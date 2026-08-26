<?php

namespace Tests\Browser;

use App\Models\ProductCategory;
use App\Models\User;
use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProductCategoryTest extends DuskTestCase
{
    use DatabaseMigrations;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesPermissionSeeder::class);

        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        $this->admin->assignRole('owner');
    }

    public function test_can_view_category_list(): void
    {
        ProductCategory::factory()->count(3)->create();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('product-categories.index'))
                ->assertVisible('@categories-table');
        });
    }

    public function test_can_create_category(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('product-categories.create'))
                ->type('@input-name', 'Snorkeling Trip')
                ->type('@input-type_slug', 'snorkeling')
                ->type('@input-sort_order', '1')
                ->press('@save-category')
                ->waitForLocation('/product-categories')
                ->assertSee('Snorkeling Trip');
        });

        $this->assertDatabaseHas('product_categories', ['type_slug' => 'snorkeling']);
    }

    public function test_can_edit_category(): void
    {
        $category = ProductCategory::factory()->create(['name' => 'Old Category']);

        $this->browse(function (Browser $browser) use ($category) {
            $browser->loginAs($this->admin)
                ->visit(route('product-categories.edit', $category))
                ->clear('@input-name')
                ->type('@input-name', 'Updated Category')
                ->press('@save-category')
                ->waitForLocation('/product-categories')
                ->assertSee('Updated Category');
        });

        $this->assertDatabaseHas('product_categories', ['id' => $category->id, 'name' => 'Updated Category']);
    }

    public function test_can_delete_category(): void
    {
        $category = ProductCategory::factory()->create(['name' => 'Hapus Kategori']);

        $this->browse(function (Browser $browser) use ($category) {
            $browser->loginAs($this->admin)
                ->visit(route('product-categories.index'))
                ->press("@delete-category-{$category->id}")
                ->waitForDialog()
                ->acceptDialog()
                ->waitForLocation('/product-categories')
                ->refresh();
        });

        $this->assertDatabaseMissing('product_categories', ['id' => $category->id]);
    }

    public function test_validation_blocks_empty_form(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('product-categories.create'))
                ->press('@save-category')
                ->pause(500)
                ->assertPathIs('/product-categories/create');
        });

        $this->assertDatabaseCount('product_categories', 0);
    }
}
