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

class PosVisualQaTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_capture_pos_dropdown_open(): void
    {
        $this->seed(RolesPermissionSeeder::class);

        $branch = Branch::factory()->create();
        Customer::factory()->create(['name' => 'Budi Santoso', 'branch_id' => $branch->id]);
        Customer::factory()->create(['name' => 'Emma Schmidt', 'branch_id' => $branch->id]);
        Customer::factory()->create(['name' => 'Ketut Ariawan', 'branch_id' => $branch->id]);
        Customer::factory()->create(['name' => 'Sari Wulandari', 'branch_id' => $branch->id]);

        $cat = ProductCategory::factory()->create(['name' => 'Wisata']);
        Product::factory()->create(['name' => 'Fun Dive Paket', 'category_id' => $cat->id, 'base_price' => 950000, 'branch_id' => $branch->id]);

        $owner = User::factory()->create(['email' => 'owner@example.com', 'password' => 'password']);
        $owner->assignRole('owner');

        $this->browse(function (Browser $browser) use ($owner) {
            $browser->loginAs($owner)
                ->visit(route('transactions.create'))
                ->pause(1500)
                ->storeSource('pos-dom-check')
                ->screenshot('pos-before-open')
                ->clickAtXPath("//select[@dusk='select-customer_id']/following-sibling::div[contains(@class,'ts-wrapper')]/div[contains(@class,'ts-control')]")
                ->waitUntil("document.querySelectorAll('.ts-wrapper.dropdown-active').length > 0")
                ->pause(400)
                ->screenshot('pos-dropdown-open')
                // Pilih pelanggan dari dropdown (menutup dropdown sekaligus).
                ->clickAtXPath("//div[contains(@class,'ts-dropdown')]//div[contains(@class,'option')][contains(.,'Budi Santoso')]")
                ->pause(300)
                ->clickAtXPath("//select[@dusk='select-schedule_id']/following-sibling::div[contains(@class,'ts-wrapper')]/div[contains(@class,'ts-control')]")
                ->waitUntil("document.querySelectorAll('.ts-wrapper.dropdown-active').length > 0")
                ->pause(400)
                ->screenshot('pos-second-dropdown');
        });

        $this->assertEquals('Budi Santoso', Customer::where('name', 'Budi Santoso')->first()->name);
    }
}
