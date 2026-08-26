<?php

namespace Tests\Feature\Finance;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Branch $branch;

    private ExpenseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(['RolesPermissionSeeder', 'ExpenseCategorySeeder']);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('owner');

        $this->branch = Branch::factory()->create();
        $this->category = ExpenseCategory::where('slug', 'operasional')->firstOrFail();
    }

    public function test_owner_can_view_expenses_index(): void
    {
        Expense::factory()->create([
            'branch_id' => $this->branch->id,
            'expense_category_id' => $this->category->id,
            'user_id' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->get(route('expenses.index'))
            ->assertOk()
            ->assertSee($this->category->name)
            ->assertSee('create-expense', false);
    }

    public function test_finance_can_create_expense(): void
    {
        $finance = User::factory()->create();
        $finance->assignRole('finance');

        $response = $this->actingAs($finance)
            ->post(route('expenses.store'), [
                'branch_id' => $this->branch->id,
                'expense_category_id' => $this->category->id,
                'description' => 'Beli air galon',
                'amount' => 150000,
                'expense_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'description' => 'Beli air galon',
            'amount' => 150000,
            'user_id' => $finance->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'expense_created',
            'user_id' => $finance->id,
        ]);
    }

    public function test_amount_must_be_non_negative(): void
    {
        $this->actingAs($this->owner)
            ->from(route('expenses.create'))
            ->post(route('expenses.store'), [
                'branch_id' => $this->branch->id,
                'expense_category_id' => $this->category->id,
                'description' => 'Negatif',
                'amount' => -1000,
                'expense_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseMissing('expenses', ['description' => 'Negatif']);
    }

    public function test_admin_cabang_cannot_create_expense_for_other_branch(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin-cabang');
        $admin->branches()->attach($this->branch);

        $otherBranch = Branch::factory()->create();

        $this->actingAs($admin)
            ->post(route('expenses.store'), [
                'branch_id' => $otherBranch->id,
                'expense_category_id' => $this->category->id,
                'description' => 'Cabang lain',
                'amount' => 50000,
                'expense_date' => now()->toDateString(),
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('expenses', ['description' => 'Cabang lain']);
    }

    public function test_admin_cabang_sees_only_own_branch_expenses(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin-cabang');
        $admin->branches()->attach($this->branch);

        $own = Expense::factory()->create([
            'branch_id' => $this->branch->id,
            'expense_category_id' => $this->category->id,
            'description' => 'Milik cabang sendiri',
        ]);
        Expense::factory()->create([
            'branch_id' => Branch::factory()->create()->id,
            'expense_category_id' => $this->category->id,
            'description' => 'Cabang sebelah',
        ]);

        $this->actingAs($admin)
            ->get(route('expenses.index'))
            ->assertOk()
            ->assertSee('Milik cabang sendiri')
            ->assertDontSee('Cabang sebelah');
    }

    public function test_generated_expense_is_locked_from_edit_and_delete(): void
    {
        $expense = Expense::factory()->create([
            'branch_id' => $this->branch->id,
            'expense_category_id' => $this->category->id,
            'ref_type' => 'payroll_period',
            'ref_id' => 99,
        ]);

        $this->actingAs($this->owner)
            ->put(route('expenses.update', $expense), [
                'branch_id' => $this->branch->id,
                'expense_category_id' => $this->category->id,
                'description' => 'Diubah manual',
                'amount' => 1,
                'expense_date' => now()->toDateString(),
            ])
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->delete(route('expenses.destroy', $expense))
            ->assertForbidden();

        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }

    public function test_kasir_cannot_access_expenses(): void
    {
        $kasir = User::factory()->create();
        $kasir->assignRole('kasir');

        $this->actingAs($kasir)
            ->get(route('expenses.index'))
            ->assertForbidden();
    }

    public function test_update_expense_saves_with_audit(): void
    {
        $expense = Expense::factory()->create([
            'branch_id' => $this->branch->id,
            'expense_category_id' => $this->category->id,
            'amount' => 100000,
        ]);

        $this->actingAs($this->owner)
            ->put(route('expenses.update', $expense), [
                'branch_id' => $this->branch->id,
                'expense_category_id' => $this->category->id,
                'description' => $expense->description,
                'amount' => 250000,
                'expense_date' => $expense->expense_date->toDateString(),
            ])
            ->assertRedirect();

        $expense->refresh();
        $this->assertEquals(250000, $expense->amount);

        $this->assertDatabaseHas('audit_logs', ['action' => 'expense_updated']);
    }
}
