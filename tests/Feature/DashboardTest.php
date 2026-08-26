<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_sees_dashboard(): void
    {
        $this->seed([RolesPermissionSeeder::class, BranchSeeder::class]);

        $user = User::factory()->create();
        $user->assignRole('owner');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard');
    }

    public function test_dashboard_shows_summary_cards(): void
    {
        $this->seed([RolesPermissionSeeder::class, BranchSeeder::class]);

        $user = User::factory()->create();
        $user->assignRole('owner');

        $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();

        foreach ([
            'card-revenue-today',
            'card-revenue-period',
            'card-expense-period',
            'card-profit-period',
            'alert-low-stock',
            // Filter periode + kartu pembanding tahun/bulan/bulan lalu.
            'dashboard-period-filter',
            'dashboard-period-comparison',
            'card-compare-year',
            'card-compare-month',
            'card-compare-last-month',
        ] as $dusk) {
            $this->assertStringContainsString($dusk, (string) $response->getContent());
        }
    }

    public function test_dashboard_period_filter_switches_range(): void
    {
        $this->seed([RolesPermissionSeeder::class, BranchSeeder::class]);

        $user = User::factory()->create();
        $user->assignRole('owner');

        foreach (['month', 'last_month', 'year'] as $period) {
            $this->actingAs($user)
                ->get(route('dashboard', ['period' => $period]))
                ->assertOk()
                ->assertSee('value="' . $period . '" selected', false);
        }

        // Periode tak dikenal fallback ke month.
        $this->actingAs($user)
            ->get(route('dashboard', ['period' => 'hack']))
            ->assertOk()
            ->assertSee('value="month" selected', false);
    }

    public function test_admin_cabang_dashboard_has_no_branch_comparison(): void
    {
        $this->seed([RolesPermissionSeeder::class, BranchSeeder::class]);

        $admin = User::factory()->create();
        $admin->assignRole('admin-cabang');
        $branch = \App\Models\Branch::firstOrFail();
        $admin->branches()->attach($branch);

        $response = $this->actingAs($admin)->get(route('dashboard'))->assertOk();

        $this->assertStringNotContainsString('dashboard-branch-table', (string) $response->getContent());
    }
}
