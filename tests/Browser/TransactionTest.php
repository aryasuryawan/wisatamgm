<?php

namespace Tests\Browser;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TransactionTest extends DuskTestCase
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

    public function test_full_pos_flow(): void
    {
        $wisata = ProductCategory::factory()->create(['name' => 'Wisata', 'type_slug' => 'wisata']);
        $product = Product::factory()->create([
            'name' => 'Fun Dive Paket',
            'category_id' => $wisata->id,
            'base_price' => 500_000,
            'branch_id' => $this->branch->id,
        ]);

        $this->browse(function (Browser $browser) use ($product) {
            $browser->loginAs($this->owner)
                ->visit(route('transactions.create'))
                ->pause(1000)
                ->storeSource('pos-debug2')
                ->click("@product-{$product->id}")
                ->waitForText('Fun Dive Paket')
                ->press('@pos-submit')
                ->waitUntil("/^\/transactions\/\d+$/.test(window.location.pathname)")
                ->assertSee('Fun Dive Paket')
                ->assertSee('Rp 555.000');
        });

        $this->assertDatabaseHas('transactions', [
            'status' => 'paid',
            'subtotal' => '500000.00',
            'tax_total' => '55000.00',
            'grand_total' => '555000.00',
        ]);
    }

    public function test_partial_payment_then_void(): void
    {
        $customer = Customer::factory()->create(['name' => 'DP Customer']);
        $tx = \App\Models\Transaction::factory()->confirmed()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'user_id' => $this->owner->id,
            'subtotal' => 100_000,
            'tax_total' => 11_000,
            'grand_total' => 111_000,
        ]);

        $this->browse(function (Browser $browser) use ($tx) {
            $browser->loginAs($this->owner)
                ->visit(route('transactions.show', $tx))
                ->assertSee('Rp 111.000');

            // Bayar sebagian.
            $browser->within('@payment-form', function (Browser $form) {
                $form->clear('@input-amount')
                    ->type('@input-amount', '50000')
                    ->press('@submit-payment');
            })
                ->waitForText('Rp 61.000')
                ->assertSee('Rp 61.000');

            // Owner void — Alpine confirm toggle, bukan native dialog.
            $browser->press('@void-transaction')
                ->waitFor('@void-confirm-yes', 10)
                ->press('@void-confirm-yes')
                ->waitForText('Dibatalkan (Void)', 10);
        });

        $this->assertDatabaseHas('transactions', ['id' => $tx->id, 'status' => 'void']);
        $this->assertDatabaseHas('payments', ['transaction_id' => $tx->id, 'amount' => '50000.00']);
    }

    public function test_can_view_transaction_list_and_filter(): void
    {
        \App\Models\Transaction::factory()->create(['branch_id' => $this->branch->id]);
        \App\Models\Transaction::factory()->voided()->create(['branch_id' => $this->branch->id]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->owner)
                ->visit(route('transactions.index'))
                ->assertVisible('@transactions-table')
                ->select('@select-status', 'void')
                ->script("document.querySelector('[dusk=\"filter-button\"]').click()");
            $browser->pause(800)
                ->assertVisible('@transactions-table');
        });
    }
}
