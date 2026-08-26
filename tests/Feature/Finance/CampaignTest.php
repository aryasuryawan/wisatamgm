<?php

namespace Tests\Feature\Finance;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\MarketingCampaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(['RolesPermissionSeeder', 'ExpenseCategorySeeder']);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('owner');

        $this->branch = Branch::factory()->create();
    }

    public function test_marketing_can_view_campaigns(): void
    {
        $marketing = User::factory()->create();
        $marketing->assignRole('marketing');

        $campaign = MarketingCampaign::factory()->forBranch($this->branch)->create();

        $this->actingAs($marketing)
            ->get(route('marketing-campaigns.index'))
            ->assertOk()
            ->assertSee($campaign->name);
    }

    public function test_create_campaign(): void
    {
        $marketing = User::factory()->create();
        $marketing->assignRole('marketing');

        $this->actingAs($marketing)
            ->post(route('marketing-campaigns.store'), [
                'branch_id' => $this->branch->id,
                'name' => 'Promo Juli',
                'channel' => 'meta_ads',
                'budget' => 5000000,
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('marketing_campaigns', [
            'name' => 'Promo Juli',
            'budget' => 5000000,
        ]);
    }

    public function test_end_date_must_be_after_start_date(): void
    {
        $this->actingAs($this->owner)
            ->post(route('marketing-campaigns.store'), [
                'branch_id' => $this->branch->id,
                'name' => 'Invalid range',
                'budget' => 1000000,
                'start_date' => now()->toDateString(),
                'end_date' => now()->subDay()->toDateString(),
            ])
            ->assertSessionHasErrors('end_date');
    }

    public function test_index_shows_over_budget_badge(): void
    {
        $campaign = MarketingCampaign::factory()->forBranch($this->branch)->create([
            'budget' => 1000000,
        ]);

        Expense::factory()->forCampaign($campaign)->create([
            'expense_category_id' => ExpenseCategory::where('slug', 'marketing')->firstOrFail()->id,
            'amount' => 1500000,
        ]);

        $this->actingAs($this->owner)
            ->get(route('marketing-campaigns.index'))
            ->assertOk()
            ->assertSee('over-budget-badge', false);
    }

    public function test_campaign_with_expenses_cannot_be_deleted(): void
    {
        $campaign = MarketingCampaign::factory()->forBranch($this->branch)->create();

        Expense::factory()->forCampaign($campaign)->create([
            'expense_category_id' => ExpenseCategory::where('slug', 'marketing')->firstOrFail()->id,
        ]);

        $this->actingAs($this->owner)
            ->delete(route('marketing-campaigns.destroy', $campaign))
            ->assertStatus(422);

        $this->assertDatabaseHas('marketing_campaigns', ['id' => $campaign->id]);
    }

    public function test_admin_cabang_sees_only_own_branch_campaigns(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin-cabang');
        $admin->branches()->attach($this->branch);

        MarketingCampaign::factory()->forBranch($this->branch)->create(['name' => 'Milik cabang']);
        MarketingCampaign::factory()->create(['branch_id' => Branch::factory()->create()->id, 'name' => 'Cabang sebelah']);

        $this->actingAs($admin)
            ->get(route('marketing-campaigns.index'))
            ->assertOk()
            ->assertSee('Milik cabang')
            ->assertDontSee('Cabang sebelah');
    }
}
