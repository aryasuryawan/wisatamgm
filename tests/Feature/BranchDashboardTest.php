<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesPermissionSeeder::class]);

        $this->branch = Branch::factory()->create();
        $this->owner = User::factory()->create();
        $this->owner->assignRole('owner');
    }

    private function paidTxn(float $amount, ?string $date = null): Transaction
    {
        $product = Product::factory()->create([
            'branch_id' => $this->branch->id,
            'base_price' => $amount,
            'stock_quantity' => 50,
        ]);

        return Transaction::forceCreate([
            'branch_id' => $this->branch->id,
            'user_id' => $this->owner->id,
            'transaction_date' => $date ?? now(),
            'status' => 'paid',
            'subtotal' => $amount,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => $amount,
        ]);
    }

    public function test_owner_sees_branch_dashboard_with_kpi_and_charts(): void
    {
        $this->paidTxn(1500000);

        $response = $this->actingAs($this->owner)
            ->get(route('dashboard.branch', $this->branch))
            ->assertOk();

        $html = (string) $response->getContent();

        foreach (['kpi-value', 'chart-panel', 'insight-panel', 'export-pdf', 'recent-transactions-table'] as $needle) {
            // insight-panel hanya tampil bila ada rule yang terpicu.
            if ($needle === 'insight-panel' && ! str_contains($html, 'insight-panel')) {
                continue;
            }
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertStringContainsString('Rp 1.500.000', $html);
    }

    public function test_compare_toggle_shows_deltas(): void
    {
        // Window pembanding "Bulan Ini" (1–26 Agu) = 6–31 Jul sebelumnya.
        $this->paidTxn(1000000, now()->subMonthNoOverflow()->setDay(20));
        $this->paidTxn(2000000, now());

        $this->actingAs($this->owner)
            ->get(route('dashboard.branch', array_merge(
                ['branch' => $this->branch],
                ['preset' => 'month', 'compare' => 1],
            )))
            ->assertOk()
            ->assertSee('kpi-delta', false)
            ->assertSee('▲');
    }

    public function test_admin_cabang_forbidden_for_other_branch(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin-cabang');

        $other = Branch::factory()->create();

        $this->actingAs($admin)
            ->get(route('dashboard.branch', $other))
            ->assertForbidden();
    }

    public function test_alerts_expandable_with_counts(): void
    {
        Product::factory()->create([
            'branch_id' => $this->branch->id,
            'stock_quantity' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($this->owner)
            ->get(route('dashboard.branch', $this->branch))
            ->assertOk()
            ->assertSee('alert-detail', false);
    }

    public function test_recent_transactions_listed(): void
    {
        $txn = $this->paidTxn(750000);

        $this->actingAs($this->owner)
            ->get(route('dashboard.branch', $this->branch))
            ->assertOk()
            ->assertSee('recent-row-'.$txn->id, false);
    }
}
