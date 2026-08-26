<?php

namespace Tests\Browser\Product;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProductTest extends DuskTestCase
{
    use DatabaseMigrations;

    private User $admin;
    private ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesPermissionSeeder::class);

        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        $this->admin->assignRole('owner');
        $this->category = ProductCategory::factory()->create(['name' => 'Wisata']);
    }

    public function test_can_view_product_list(): void
    {
        Product::factory()->count(3)->create(['category_id' => $this->category->id]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('products.index'))
                ->assertVisible('@products-table');
        });
    }

    public function test_can_search_product(): void
    {
        Product::factory()->create(['name' => 'Paket Snorkeling', 'category_id' => $this->category->id]);
        Product::factory()->create(['name' => 'Paket Diving', 'category_id' => $this->category->id]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('products.index'))
                ->type('@search-input', 'Snorkeling')
                ->press('Filter')
                ->assertSee('Paket Snorkeling')
                ->assertDontSee('Paket Diving');
        });
    }

    public function test_can_create_product(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('products.create'))
                ->type('@input-name', 'Dive Package Premium')
                ->select('@select-category_id', $this->category->id)
                ->type('@input-base_price', '2500000')
                ->type('@input-unit', 'pax')
                ->type('@input-stock_quantity', '10')
                ->press('@save-product')
                ->waitForLocation('/products')
                ->assertSee('Dive Package Premium');
        });

        $this->assertDatabaseHas('products', ['name' => 'Dive Package Premium']);
    }

    public function test_can_edit_product(): void
    {
        $product = Product::factory()->create(['name' => 'Old Name', 'category_id' => $this->category->id]);

        $this->browse(function (Browser $browser) use ($product) {
            $browser->loginAs($this->admin)
                ->visit(route('products.edit', $product))
                ->clear('@input-name')
                ->type('@input-name', 'New Name')
                ->clear('@input-base_price')
                ->type('@input-base_price', '1500000')
                ->press('@save-product')
                ->waitForLocation('/products')
                ->assertSee('New Name');
        });

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'New Name']);
    }

    public function test_can_delete_product(): void
    {
        $product = Product::factory()->create(['name' => 'Hapus Product', 'category_id' => $this->category->id]);

        $this->browse(function (Browser $browser) use ($product) {
            $browser->loginAs($this->admin)
                ->visit(route('products.index'))
                ->press("@delete-product-{$product->id}")
                ->waitForDialog()
                ->acceptDialog()
                ->waitForLocation('/products')
                ->refresh();
        });

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_can_filter_by_category(): void
    {
        Product::factory()->create(['name' => 'Dive Gear', 'category_id' => $this->category->id]);
        $other = ProductCategory::factory()->create(['name' => 'Snorkeling']);
        Product::factory()->create(['name' => 'Snorkel Set', 'category_id' => $other->id]);

        $this->browse(function (Browser $browser) use ($other) {
            $browser->loginAs($this->admin)
                ->visit(route('products.index'))
                ->select('@filter-category', $other->id)
                ->press('Filter')
                ->assertSee('Snorkel Set')
                ->assertDontSee('Dive Gear');
        });
    }

    public function test_validation_blocks_empty_form(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('products.create'))
                ->press('@save-product')
                ->pause(500)
                ->assertPathIs('/products/create');
        });

        $this->assertDatabaseCount('products', 0);
    }
}
