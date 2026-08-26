<?php

namespace App\Domain\Payroll\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Finance\Services\ExpenseService;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    public const EXPENSE_REF_TYPE = 'payroll_period';

    public function __construct(private ExpenseService $expenseService) {}

    /**
     * Hitung ulang seluruh item payroll untuk periode (hanya saat draft).
     *
     * Sumber komisi: schedule_staff pada jadwal COMPLETED di cabang periode,
     * yang tanggalnya jatuh dalam rentang periode. Gaji pokok dari snapshot
     * users.base_salary saat generate.
     */
    public function generateItems(PayrollPeriod $period, User $actor): int
    {
        $this->assertStatus($period, 'draft');

        $scheduleStaff = Schedule::query()
            ->where('branch_id', $period->branch_id)
            ->where('status', 'completed')
            ->whereBetween('date_start', [$period->period_start->copy()->startOfDay(), $period->period_end->copy()->endOfDay()])
            ->with(['staff', 'participants'])
            ->get()
            ->flatMap(fn ($schedule) => $schedule->staff->map(fn ($staff) => [
                'user_id' => $staff->user_id,
                'pax' => $schedule->participants()->count(),
            ]));

        // Staf tetap cabang ikut digaji meski tidak handle trip.
        $branchUserIds = DB::table('user_branch')
            ->where('branch_id', $period->branch_id)
            ->pluck('user_id');

        /** @var array<int, array{trips: int, pax: int}> $stats */
        $stats = [];

        foreach ($scheduleStaff as $assignment) {
            $stats[$assignment['user_id']]['trips'] = ($stats[$assignment['user_id']]['trips'] ?? 0) + 1;
            $stats[$assignment['user_id']]['pax'] = ($stats[$assignment['user_id']]['pax'] ?? 0) + $assignment['pax'];
        }

        $users = User::query()
            ->where('is_active', true)
            ->whereIn('id', $branchUserIds->merge(array_keys($stats))->unique())
            ->get()
            ->keyBy('id');

        foreach ($stats as $userId => $stat) {
            if (! $users->has($userId)) {
                continue;
            }
            $users[$userId]->setAttribute('_trips', $stat['trips']);
            $users[$userId]->setAttribute('_pax', $stat['pax']);
        }

        return DB::transaction(function () use ($period, $users) {
            $existingIds = [];

            foreach ($users as $user) {
                $trips = (int) ($user->_trips ?? 0);
                $pax = (int) ($user->_pax ?? 0);

                $commissionTotal = match ($user->commission_type) {
                    'per_pax' => (float) $user->commission_rate * $pax,
                    'per_trip' => (float) $user->commission_rate * $trips,
                    default => 0.0,
                };

                $item = PayrollItem::updateOrCreate(
                    ['payroll_period_id' => $period->id, 'user_id' => $user->id],
                    [
                        'base_salary' => (float) ($user->base_salary ?? 0),
                        'trips_count' => $trips,
                        'pax_count' => $pax,
                        'commission_total' => round($commissionTotal, 2),
                    ]
                );

                $item->recalculateNet();
                $item->save();

                $existingIds[] = $item->id;
            }

            PayrollItem::where('payroll_period_id', $period->id)
                ->whereNotIn('id', $existingIds)
                ->delete();

            AuditLogger::log('payroll_items_generated', $period, null, [
                'items_count' => count($existingIds),
                'total_net' => $period->totalNet(),
            ]);

            return count($existingIds);
        });
    }

    public function approve(PayrollPeriod $period, User $actor): void
    {
        $this->assertStatus($period, 'draft');

        $before = $period->only(['status', 'approved_by', 'approved_at']);

        $period->status = 'approved';
        $period->approved_by = $actor->id;
        $period->approved_at = now();
        $period->save();

        AuditLogger::log('payroll_period_approved', $period, $before, $period->only(['status', 'approved_by', 'approved_at']));
    }

    /**
     * Tutup periode: total payroll otomatis jadi expense kategori "Gaji"
     * agar laba-rugi punya satu sumber data (System Design §4).
     */
    public function close(PayrollPeriod $period, User $actor): Expense
    {
        $this->assertStatus($period, 'approved');

        $salaryCategory = ExpenseCategory::where('slug', 'gaji')->firstOrFail();

        $expense = Expense::create([
            'branch_id' => $period->branch_id,
            'expense_category_id' => $salaryCategory->id,
            'user_id' => $actor->id,
            'ref_type' => self::EXPENSE_REF_TYPE,
            'ref_id' => $period->id,
            'description' => __('ui.payroll_expense_description', [
                'start' => $period->period_start->format('d M Y'),
                'end' => $period->period_end->format('d M Y'),
            ]),
            'amount' => round($period->totalNet(), 2),
            'expense_date' => $period->period_end,
        ]);

        $before = $period->only(['status', 'closed_at']);

        $period->status = 'closed';
        $period->closed_at = now();
        $period->save();

        AuditLogger::log('payroll_period_closed', $period, $before, [
            'status' => $period->status,
            'closed_at' => $period->closed_at?->toDateTimeString(),
            'expense_id' => $expense->id,
            'amount' => $expense->amount,
        ]);

        return $expense;
    }

    public function updateDeduction(PayrollItem $item, float $deduction, User $actor): void
    {
        $this->assertStatus($item->period, 'draft');
        $this->assertEditableAmount($deduction);

        $before = $item->only(['deduction', 'net_total']);

        $item->deduction = round($deduction, 2);
        $item->recalculateNet();
        $item->save();

        AuditLogger::log('payroll_deduction_updated', $item, $before, $item->only(['deduction', 'net_total']));
    }

    private function assertStatus(PayrollPeriod $period, string $expected): void
    {
        if ($period->status !== $expected) {
            throw ValidationException::withMessages([
                'period' => __('ui.period_not_editable'),
            ]);
        }
    }

    private function assertEditableAmount(float $amount): void
    {
        if ($amount < 0 || $amount > 9999999999.99) {
            throw ValidationException::withMessages([
                'deduction' => __('validation.numeric', ['attribute' => __('ui.deduction')]),
            ]);
        }
    }
}
