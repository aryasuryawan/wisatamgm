<?php

namespace Tests\Feature\Finance;

use App\Domain\Payroll\Services\PayrollService;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\Schedule;
use App\Models\ScheduleParticipant;
use App\Models\ScheduleStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(['RolesPermissionSeeder', 'ExpenseCategorySeeder']);

        $this->branch = Branch::factory()->create();
    }

    private function owner(): User
    {
        $user = User::factory()->create();
        $user->assignRole('owner');

        return $user;
    }

    private function period(array $attrs = []): PayrollPeriod
    {
        return PayrollPeriod::factory()->create([
            'branch_id' => $this->branch->id,
            'period_start' => now()->subMonth()->startOfMonth(),
            'period_end' => now()->subMonth()->endOfMonth(),
            'created_by' => $this->owner()->id,
            ...$attrs,
        ]);
    }

    private function completedSchedule(User $staff, int $pax): Schedule
    {
        $schedule = Schedule::factory()->completed()->create([
            'branch_id' => $this->branch->id,
            'date_start' => now()->subMonth()->startOfMonth()->addDays(3),
            'date_end' => now()->subMonth()->startOfMonth()->addDays(3)->addHours(6),
            'capacity' => max(4, $pax),
        ]);

        ScheduleStaff::create([
            'schedule_id' => $schedule->id,
            'user_id' => $staff->id,
            'role_in_trip' => 'guide',
        ]);

        for ($i = 0; $i < $pax; $i++) {
            ScheduleParticipant::factory()->create(['schedule_id' => $schedule->id]);
        }

        return $schedule;
    }

    private function generate(PayrollPeriod $period, ?User $actor = null): void
    {
        app(PayrollService::class)
            ->generateItems($period, $actor ?? $this->owner());
    }

    public function test_commission_per_pax_math(): void
    {
        $guide = User::factory()->create([
            'commission_type' => 'per_pax',
            'commission_rate' => 50000,
        ]);
        $this->completedSchedule($guide, 4);

        $period = $this->period();
        $this->generate($period);

        $item = PayrollItem::where('payroll_period_id', $period->id)
            ->where('user_id', $guide->id)->firstOrFail();

        $this->assertEquals(4, $item->pax_count);
        $this->assertEquals(1, $item->trips_count);
        $this->assertEquals(200000, (float) $item->commission_total);
        $this->assertEquals(200000, (float) $item->net_total);
    }

    public function test_commission_per_trip_math(): void
    {
        $instructor = User::factory()->create([
            'commission_type' => 'per_trip',
            'commission_rate' => 150000,
        ]);
        $this->completedSchedule($instructor, 2);
        $this->completedSchedule($instructor, 6);

        $period = $this->period();
        $this->generate($period);

        $item = PayrollItem::where('payroll_period_id', $period->id)
            ->where('user_id', $instructor->id)->firstOrFail();

        $this->assertEquals(2, $item->trips_count);
        $this->assertEquals(300000, (float) $item->commission_total);
    }

    public function test_salaried_staff_without_trips_is_included(): void
    {
        $staff = User::factory()->create([
            'base_salary' => 3000000,
            'commission_type' => 'none',
        ]);
        $staff->branches()->attach($this->branch);

        $period = $this->period();
        $this->generate($period);

        $item = PayrollItem::where('payroll_period_id', $period->id)
            ->where('user_id', $staff->id)->firstOrFail();

        $this->assertEquals(0, (float) $item->commission_total);
        $this->assertEquals(3000000, (float) $item->net_total);
    }

    public function test_regenerate_does_not_duplicate(): void
    {
        $guide = User::factory()->create([
            'commission_type' => 'per_pax',
            'commission_rate' => 50000,
        ]);
        $this->completedSchedule($guide, 3);

        $period = $this->period();
        $this->generate($period);
        $this->generate($period);

        $count = PayrollItem::where('payroll_period_id', $period->id)->count();
        $this->assertEquals(1, $count);
    }

    public function test_generate_blocked_when_not_draft(): void
    {
        $period = $this->period(['status' => 'approved', 'approved_at' => now()]);

        $this->actingAs($this->owner())
            ->post(route('payroll.generate', $period))
            ->assertForbidden();

        $this->assertEquals(0, PayrollItem::where('payroll_period_id', $period->id)->count());
    }

    public function test_approve_then_close_generates_salary_expense(): void
    {
        $guide = User::factory()->create([
            'commission_type' => 'per_pax',
            'commission_rate' => 100000,
        ]);
        $this->completedSchedule($guide, 5);

        $owner = $this->owner();
        $period = $this->period(['created_by' => $owner->id]);
        $this->generate($period, $owner);

        $this->actingAs($owner)->post(route('payroll.approve', $period))->assertRedirect();
        $period->refresh();
        $this->assertEquals('approved', $period->status);
        $this->assertNotNull($period->approved_by);

        $this->actingAs($owner)->post(route('payroll.close', $period))
            ->assertRedirect(route('expenses.index'));

        $period->refresh();
        $this->assertEquals('closed', $period->status);

        $expense = Expense::where('ref_type', 'payroll_period')->where('ref_id', $period->id)->firstOrFail();
        $this->assertEquals(500000, (float) $expense->amount);
        $this->assertEquals('gaji', $expense->category->slug);

        // Expense otomatis terkunci dari edit manual
        $this->actingAs($owner)
            ->put(route('expenses.update', $expense), [
                'branch_id' => $expense->branch_id,
                'expense_category_id' => $expense->expense_category_id,
                'description' => 'Diubah',
                'amount' => 1,
                'expense_date' => $expense->expense_date->toDateString(),
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('audit_logs', ['action' => 'payroll_period_approved']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payroll_period_closed']);
    }

    public function test_finance_cannot_approve_period(): void
    {
        $finance = User::factory()->create();
        $finance->assignRole('finance');

        $period = $this->period();

        $this->actingAs($finance)
            ->post(route('payroll.approve', $period))
            ->assertForbidden();

        $this->assertEquals('draft', $period->fresh()->status);
    }

    public function test_overlapping_period_is_rejected(): void
    {
        $owner = $this->owner();
        $start = now()->subMonth()->startOfMonth();
        $end = now()->subMonth()->endOfMonth();
        $this->period(['created_by' => $owner->id]);

        $this->actingAs($owner)
            ->post(route('payroll.store'), [
                'branch_id' => $this->branch->id,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
            ])
            ->assertSessionHasErrors('period_start');
    }

    public function test_deduction_update_recalculates_net(): void
    {
        $guide = User::factory()->create([
            'commission_type' => 'per_pax',
            'commission_rate' => 100000,
        ]);
        $this->completedSchedule($guide, 3);

        $owner = $this->owner();
        $period = $this->period(['created_by' => $owner->id]);
        $this->generate($period, $owner);

        $item = PayrollItem::where('payroll_period_id', $period->id)
            ->where('user_id', $guide->id)->firstOrFail();

        $this->actingAs($owner)
            ->put(route('payroll.deduction', [$period, $item]), [
                'deduction' => 50000,
            ])
            ->assertRedirect();

        $item->refresh();
        $this->assertEquals(50000, (float) $item->deduction);
        $this->assertEquals(250000, (float) $item->net_total);
    }

    public function test_admin_cabang_has_no_payroll_access(): void
    {
        PayrollPeriod::factory()->create(['branch_id' => $this->branch->id]);

        $admin = User::factory()->create();
        $admin->assignRole('admin-cabang');
        $admin->branches()->attach($this->branch);

        $this->actingAs($admin)->get(route('payroll.index'))->assertForbidden();
    }
}
