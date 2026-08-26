<?php

namespace Tests\Browser;

use App\Models\Branch;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DiscountTest extends DuskTestCase
{
    use DatabaseMigrations;

    private User $owner;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesPermissionSeeder::class);

        $this->branch = Branch::factory()->create();
        $this->owner = User::factory()->create([
            'email' => 'owner@example.com',
            'password' => 'password',
        ]);
        $this->owner->assignRole('owner');
    }

    public function test_can_view_discount_list(): void
    {
        Discount::factory()->count(3)->create();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->owner)
                ->visit(route('discounts.index'))
                ->assertVisible('@discounts-table');
        });
    }

    public function test_can_create_discount(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->owner)
                ->visit(route('discounts.create'))
                ->type('@input-code', 'DUSK15')
                ->type('@input-name', 'Promo Dusk 15%')
                ->select('@select-type', 'percent')
                ->clear('@input-value')
                ->type('@input-value', '15')
                ->press('@save-discount')
                ->waitForLocation('/discounts')
                ->assertSee('DUSK15');
        });

        $this->assertDatabaseHas('discounts', [
            'code' => 'DUSK15',
            'value' => '15.00',
        ]);
    }

    public function test_pos_applies_discount_code(): void
    {
        Discount::factory()->percent(10)->create([
            'code' => 'POSHEMAT',
            'name' => 'POS Hemat',
        ]);
        $wisata = ProductCategory::factory()->create(['type_slug' => 'wisata']);
        $product = Product::factory()->create([
            'name' => 'Paket Diskon Test',
            'category_id' => $wisata->id,
            'base_price' => 200000,
            'branch_id' => $this->branch->id,
        ]);

        $this->browse(function (Browser $browser) use ($product) {
            $browser->loginAs($this->owner)
                ->visit(route('transactions.create'))
                ->type('@input-discount_code', 'POSHEMAT')
                ->click("@product-{$product->id}")
                ->waitForText('Paket Diskon Test')
                ->press('@pos-submit')
                ->waitUntil("/^\/transactions\/\d+$/.test(window.location.pathname)")
                ->assertSee('Paket Diskon Test')
                ->assertSee('Rp 200.000')
                ->assertSee('Rp 19.800');
        });

        // Subtotal 200.000, diskon 10% = 20.000, PPN 11% x 180.000 = 19.800, total 199.800.
        $this->assertDatabaseHas('transactions', [
            'subtotal' => '200000.00',
            'discount_total' => '20000.00',
            'tax_total' => '19800.00',
            'grand_total' => '199800.00',
        ]);

        $this->assertDatabaseHas('discount_usages', [
            'discount_id' => Discount::where('code', 'POSHEMAT')->value('id'),
            'amount' => '20000.00',
        ]);
    }

    public function test_can_edit_discount(): void
    {
        $discount = Discount::factory()->create(['code' => 'EDITME']);

        $this->browse(function (Browser $browser) use ($discount) {
            $browser->loginAs($this->owner)
                ->visit(route('discounts.edit', $discount))
                ->clear('@input-name')
                ->type('@input-name', 'Nama Baru')
                ->press('@save-discount')
                ->waitForLocation('/discounts')
                ->assertSee('Nama Baru');
        });

        $this->assertDatabaseHas('discounts', ['id' => $discount->id, 'name' => 'Nama Baru']);
    }

    public function test_can_delete_unused_discount(): void
    {
        $discount = Discount::factory()->create(['code' => 'HAPUSKU']);

        $this->browse(function (Browser $browser) use ($discount) {
            $browser->loginAs($this->owner)
                ->visit(route('discounts.index'))
                ->press("@delete-discount-{$discount->id}")
                ->waitForDialog()
                ->acceptDialog()
                ->pause(800)
                ->refresh();
        });

        $this->assertDatabaseMissing('discounts', ['id' => $discount->id]);
    }
}
