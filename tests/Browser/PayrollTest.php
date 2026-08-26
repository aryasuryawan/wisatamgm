<?php

namespace Tests\Browser;

use App\Models\Branch;
use App\Models\ScheduleParticipant;
use App\Models\ScheduleStaff;
use App\Models\User;
use Database\Seeders\ExpenseCategorySeeder;
use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PayrollTest extends DuskTestCase
{
    use DatabaseMigrations;

    private User $owner;
    private User $guide;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolesPermissionSeeder::class, ExpenseCategorySeeder::class]);

        $this->branch = Branch::factory()->create();
        $this->guide = User::factory()->create([
            'name' => 'Wayan Guide',
            'commission_type' => 'per_pax',
            'commission_rate' => 100000,
        ]);

        $this->owner = User::factory()->create([
            'email' => 'owner@example.com',
            'password' => 'password',
        ]);
        $this->owner->assignRole('owner');

        $schedule = \App\Models\Schedule::factory()->completed()->create([
            'branch_id' => $this->branch->id,
            'date_start' => now()->subMonth()->startOfMonth()->addDays(5),
            'date_end' => now()->subMonth()->startOfMonth()->addDays(5)->addHours(6),
            'capacity' => 4,
        ]);
        ScheduleStaff::create([
            'schedule_id' => $schedule->id,
            'user_id' => $this->guide->id,
            'role_in_trip' => 'guide',
        ]);
        ScheduleParticipant::factory()->count(2)->create(['schedule_id' => $schedule->id]);
    }

    public function test_full_payroll_flow_create_generate_approve_close(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->owner)
                ->visit(route('payroll.create'))
                ->select('@select-branch_id', $this->branch->id)
                ->press('@save-period')
                ->waitUntil("/^\/payroll\/\d+$/.test(window.location.pathname)")
                ->press('@generate-items')
                ->waitForText('Wayan Guide')
                ->assertSee('Rp 200.000');

            // Approve (dengan dialog konfirmasi)
            $browser->press('@approve-period')
                ->waitForDialog()
                ->acceptDialog()
                ->waitUntilMissing('@approve-period', 10);

            // Close (dengan dialog konfirmasi) → redirect ke daftar biaya
            $browser->press('@close-period')
                ->waitForDialog()
                ->acceptDialog()
                ->waitForLocation('/expenses')
                ->assertVisible('@expenses-table')
                ->assertSee('Payroll');
        });

        $period = \App\Models\PayrollPeriod::firstOrFail();
        $this->assertEquals('closed', $period->status);

        $this->assertDatabaseHas('expenses', [
            'ref_type' => 'payroll_period',
            'ref_id' => $period->id,
            'amount' => '200000.00',
        ]);
    }
}
