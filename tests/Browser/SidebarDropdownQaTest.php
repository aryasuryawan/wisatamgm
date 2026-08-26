<?php

namespace Tests\Browser;

use App\Models\User;
use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SidebarDropdownQaTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_user_dropdown_fully_visible(): void
    {
        $this->seed(RolesPermissionSeeder::class);

        $owner = User::factory()->create(['email' => 'owner@example.com', 'password' => 'password']);
        $owner->assignRole('owner');

        $this->browse(function (Browser $browser) use ($owner) {
            $browser->loginAs($owner)
                ->visit(route('dashboard'))
                ->click('@user-menu')
                ->pause(500)
                ->screenshot('sidebar-user-dropdown')
                // Logout button harus visible & dalam viewport saat dropdown terbuka.
                ->assertVisible('@logout-button');
        });
    }
}
