<?php

namespace Tests\Feature\Report;

use App\Domain\Report\Services\ReportService;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchA;

    private Branch $branchB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(['RolesPermissionSeeder', 'ExpenseCategorySeeder', 'ProductCategorySeeder']);

        $this->branchA = Branch::factory()->create();
        $this->branchB = Branch::factory()->create();
    }

    private function owner(): User
    {
        $user = User::factory()->create();
        $user->assignRole('owner');

        return $user;
    }

    private function paidTransaction(Branch $branch, int|float $amount, ?string $date = null): Transaction
    {
        /** @var Product $product */
        $product = Product::factory()->create([
            'base_price' => $amount,
            'branch_id' => $branch->id,
            'stock_quantity' => 0,
        ]);

        $transaction = Transaction::create([
            'branch_id' => $branch->id,
            'customer_id' => Customer::factory()->create()->id,
            'user_id' => $this->owner()->id,
            'transaction_date' => ($date ?? now())->format('Y-m-d H:i:s'),
            'status' => 'draft',
            'subtotal' => $amount,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => $amount,
        ]);

        // Item tanpa stok (kategori wisata) supaya tidak memicu stock movement.
        $transaction->items()->create([
            'product_id' => $product->id,
            'qty' => 1,
            'price' => $amount,
        ]);

        Payment::create([
            'transaction_id' => $transaction->id,
            'method' => 'cash',
            'amount' => $amount,
            'paid_at' => now(),
        ]);

        $transaction->status = 'paid';
        $transaction->save();

        return $transaction;
    }

    public function test_profit_and_loss_math(): void
    {
        $this->paidTransaction($this->branchA, 1000000);
        $this->paidTransaction($this->branchA, 500000);

        Expense::create([
            'branch_id' => $this->branchA->id,
            'expense_category_id' => 1,
            'user_id' => $this->owner()->id,
            'description' => 'Listrik',
            'amount' => 300000,
            'expense_date' => now(),
        ]);

        $service = ReportService::make(null);
        $pl = $service->profitAndLoss();

        $this->assertEquals(1500000, $pl['revenue']);
        $this->assertEquals(300000, $pl['expense']);
        $this->assertEquals(1200000, $pl['profit']);
    }

    public function test_draft_transactions_are_not_revenue(): void
    {
        $txn = $this->paidTransaction($this->branchA, 750000);
        $txn->status = 'draft';
        $txn->save();

        $service = ReportService::make(null);
        $this->assertEquals(0.0, $service->revenue());
    }

    public function test_per_branch_comparison(): void
    {
        $this->paidTransaction($this->branchA, 800000);
        $this->paidTransaction($this->branchB, 200000);

        $rows = collect(ReportService::make(null)->perBranch());

        $rowA = $rows->firstWhere('branch.id', $this->branchA->id);
        $rowB = $rows->firstWhere('branch.id', $this->branchB->id);

        $this->assertEquals(800000, $rowA['revenue']);
        $this->assertEquals(200000, $rowB['revenue']);
    }

    public function test_sales_per_category_and_top_lists(): void
    {
        $this->paidTransaction($this->branchA, 400000);

        $service = ReportService::make(null);

        $categories = $service->salesPerCategory();
        $this->assertNotEmpty($categories);
        $this->assertEquals(400000, $categories[0]['total']);

        $topProducts = $service->topProducts();
        $this->assertCount(1, $topProducts);
        $this->assertEquals(400000, $topProducts[0]['total']);

        $topCustomers = $service->topCustomers();
        $this->assertCount(1, $topCustomers);
        $this->assertEquals(400000, $topCustomers[0]['total']);
    }

    public function test_admin_cabang_report_scoped_to_own_branch(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin-cabang');
        $admin->branches()->attach($this->branchA);

        $this->paidTransaction($this->branchA, 300000);
        $this->paidTransaction($this->branchB, 999000);

        $response = $this->actingAs($admin)->get(route('reports.index'))->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('Rp 300.000', $html);
        $this->assertStringNotContainsString('Rp 999.000', $html);
    }

    public function test_kasir_cannot_access_reports(): void
    {
        $kasir = User::factory()->create();
        $kasir->assignRole('kasir');

        $this->actingAs($kasir)->get(route('reports.index'))->assertForbidden();
    }
}
