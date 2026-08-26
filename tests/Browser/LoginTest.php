<?php

namespace Tests\Browser;

use App\Models\User;
use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_user_can_login_and_logout_via_ui(): void
    {
        $this->seed(RolesPermissionSeeder::class);

        User::factory()->create([
            'email' => 'owner@tulambenscuba.test',
            'password' => 'password',
        ])->assignRole('owner');

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('@input-email', 'owner@tulambenscuba.test')
                ->type('@input-password', 'wrong-password')
                ->press('@login-button')
                ->waitForText('Email atau password salah.')
                ->type('@input-email', 'owner@tulambenscuba.test')
                ->type('@input-password', 'password')
                ->press('@login-button')
                ->waitForLocation('/dashboard')
                ->assertPathIs('/dashboard')
                ->press('@user-menu')
                ->click('@logout-button')
                ->waitForLocation('/login')
                ->assertPathIs('/login');
        });
    }
}
