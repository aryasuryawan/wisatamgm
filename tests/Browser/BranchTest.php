<?php

namespace Tests\Browser;

use App\Models\User;
use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class BranchTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_owner_can_login_and_create_branch(): void
    {
        $this->seed(RolesPermissionSeeder::class);

        User::factory()->create([
            'email' => 'owner@tulambenscuba.test',
            'password' => 'password',
        ])->assignRole('owner');

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('@input-email', 'owner@tulambenscuba.test')
                ->type('@input-password', 'password')
                ->press('@login-button')
                ->waitForLocation('/dashboard')
                ->visit(route('branches.create'))
                ->type('@input-name', 'Tulamben Wreck Divers')
                ->select('@select-brand', 'tulambenscuba')
                ->press('@save-branch')
                ->waitUntil("/^\/branches\/\d+\/edit$/.test(window.location.pathname)")
                ->assertInputValue('@input-name', 'Tulamben Wreck Divers');
        });

        $this->assertDatabaseHas('branches', ['name' => 'Tulamben Wreck Divers']);
    }
}
