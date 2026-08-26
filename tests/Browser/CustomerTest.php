<?php

namespace Tests\Browser;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CustomerTest extends DuskTestCase
{
    use DatabaseMigrations;

    private User $admin;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesPermissionSeeder::class);

        $this->branch = Branch::factory()->create();
        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        $this->admin->assignRole('admin-cabang');
        $this->admin->branches()->attach($this->branch->id);
    }

    public function test_can_view_customer_list(): void
    {
        Customer::factory()->count(3)->create(['branch_id' => $this->branch->id]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('customers.index'))
                ->assertVisible('@customers-table');
        });
    }

    public function test_can_search_customer(): void
    {
        Customer::factory()->create(['name' => 'Budi Darmawan', 'branch_id' => $this->branch->id]);
        Customer::factory()->create(['name' => 'Siti Rahayu', 'branch_id' => $this->branch->id]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('customers.index'))
                ->type('@search-input', 'Budi')
                ->press('Cari')
                ->assertSee('Budi Darmawan')
                ->assertDontSee('Siti Rahayu');
        });
    }

    public function test_can_create_customer(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('customers.create'))
                ->type('@input-name', 'Joko Widodo')
                ->type('@input-phone', '08123456789')
                ->select('@select-nationality_type', 'indonesia')
                ->select('@select-source', 'organic')
                ->press('@save-customer')
                ->waitUntil("/^\/customers\/\d+\/edit$/.test(window.location.pathname)")
                ->assertSee('Joko Widodo');
        });

        $this->assertDatabaseHas('customers', ['name' => 'Joko Widodo']);
    }

    public function test_can_create_international_customer(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('customers.create'))
                ->type('@input-name', 'John Smith')
                ->type('@input-phone', '+1234567890')
                ->select('@select-nationality_type', 'international')
                ->press('@save-customer')
                ->waitUntil("/^\/customers\/\d+\/edit$/.test(window.location.pathname)");
        });

        $this->assertDatabaseHas('customers', [
            'name' => 'John Smith',
            'nationality_type' => 'international',
        ]);
    }

    public function test_can_edit_customer(): void
    {
        $customer = Customer::factory()->create(['branch_id' => $this->branch->id]);

        $this->browse(function (Browser $browser) use ($customer) {
            $browser->loginAs($this->admin)
                ->visit(route('customers.edit', $customer))
                ->clear('@input-name')
                ->type('@input-name', 'Updated Name')
                ->press('@save-customer')
                ->waitUntil("/^\/customers\/\d+\/edit$/.test(window.location.pathname)")
                ->assertSee('Updated Name');
        });

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Updated Name']);
    }

    public function test_can_view_customer_profile(): void
    {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'name' => 'Profile Test',
            'email' => 'profile@test.com',
        ]);

        $this->browse(function (Browser $browser) use ($customer) {
            $browser->loginAs($this->admin)
                ->visit(route('customers.show', $customer))
                ->assertSee('Profile Test')
                ->assertSee('profile@test.com');
        });
    }

    public function test_can_filter_by_nationality(): void
    {
        Customer::factory()->indonesian()->create(['name' => 'Budi', 'branch_id' => $this->branch->id]);
        Customer::factory()->international()->create(['name' => 'John', 'branch_id' => $this->branch->id]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('customers.index'))
                ->select('@filter-nationality', 'indonesia')
                ->press('Cari')
                ->assertSee('Budi')
                ->assertDontSee('John');
        });
    }

    public function test_can_delete_customer(): void
    {
        $owner = User::factory()->create([
            'email' => 'owner@example.com',
            'password' => 'password',
        ]);
        $owner->assignRole('owner');

        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'name' => 'Hapus Saya',
        ]);

        $this->browse(function (Browser $browser) use ($owner, $customer) {
            $browser->loginAs($owner)
                ->visit(route('customers.index'))
                ->press("@delete-customer-{$customer->id}")
                ->waitForDialog()
                ->acceptDialog()
                ->pause(1000)
                ->refresh();
        });

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }
}
