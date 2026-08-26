<?php

namespace Tests\Browser;

use App\Models\Branch;
use App\Models\User;
use Database\Seeders\ExpenseCategorySeeder;
use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class FinanceTest extends DuskTestCase
{
    use DatabaseMigrations;

    private User $owner;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolesPermissionSeeder::class, ExpenseCategorySeeder::class]);

        $this->branch = Branch::factory()->create();
        $this->owner = User::factory()->create([
            'email' => 'owner@example.com',
            'password' => 'password',
        ]);
        $this->owner->assignRole('owner');
    }

    public function test_owner_can_create_expense(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->owner)
                ->visit(route('expenses.create'))
                ->select('@select-branch_id', $this->branch->id)
                ->select('@select-expense_category_id', 1)
                ->type('@input-amount', '250000')
                ->type('@input-description', 'Beli air mineral')
                ->press('@save-expense')
                ->waitForLocation('/expenses')
                ->assertVisible('@expenses-table')
                ->assertSee('Beli air mineral');
        });

        $this->assertDatabaseHas('expenses', [
            'description' => 'Beli air mineral',
            'amount' => '250000.00',
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_expense_list_shows_rows(): void
    {
        \App\Models\Expense::factory()->create([
            'branch_id' => $this->branch->id,
            'expense_category_id' => 1,
            'user_id' => $this->owner->id,
            'description' => 'List Dusk Expense',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->owner)
                ->visit(route('expenses.index'))
                ->assertVisible('@expenses-table')
                ->assertSee('List Dusk Expense');
        });
    }

    public function test_owner_can_create_campaign(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->owner)
                ->visit(route('marketing-campaigns.create'))
                ->type('@input-name', 'Promo Dusk Ads')
                ->select('@select-channel', 'meta_ads')
                ->type('@input-budget', '5000000')
                ->press('@save-campaign')
                ->waitForLocation('/marketing-campaigns')
                ->assertVisible('@campaigns-table')
                ->assertSee('Promo Dusk Ads');
        });

        $this->assertDatabaseHas('marketing_campaigns', [
            'name' => 'Promo Dusk Ads',
            'budget' => '5000000.00',
        ]);
    }
}
