<?php

namespace App\Http\Controllers\Finance;

use App\Domain\Payroll\Services\PayrollService;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PayrollPeriodController extends Controller
{
    public function __construct(private PayrollService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PayrollPeriod::class);

        $query = PayrollPeriod::with(['branch', 'items'])
            ->orderByDesc('period_start');

        if (auth()->user()->hasRole('admin-cabang')) {
            $branchIds = auth()->user()->branches()->pluck('branches.id');
            $query->forBranches($branchIds->all());
        }

        return view('finance.payroll.index', [
            'periods' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', PayrollPeriod::class);

        return view('finance.payroll.create', [
            'branches' => $this->editableBranches(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PayrollPeriod::class);

        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->assertAllowedBranch((int) $data['branch_id']);
        $this->assertNoOverlap($data['branch_id'], $data['period_start'], $data['period_end']);

        $period = PayrollPeriod::create($data + ['created_by' => $request->user()->id]);

        return redirect()
            ->route('payroll.show', $period)
            ->with('success', __('ui.period_created'));
    }

    public function show(PayrollPeriod $payroll): View
    {
        $this->authorize('viewAny', PayrollPeriod::class);

        $staffPool = $this->staffPool($payroll);

        return view('finance.payroll.show', [
            'period' => $payroll,
            'items' => $payroll->items()->with('user')->orderBy('user_id')->get(),
            'staffPool' => $staffPool,
        ]);
    }

    public function generate(PayrollPeriod $payroll, Request $request): RedirectResponse
    {
        $this->authorize('update', $payroll);

        $count = $this->service->generateItems($payroll, $request->user());

        return redirect()
            ->route('payroll.show', $payroll)
            ->with('success', __('ui.items_generated').' ('.$count.')');
    }

    public function updateDeduction(Request $request, PayrollPeriod $payroll, PayrollItem $item): RedirectResponse
    {
        $this->authorize('update', $payroll);

        abort_unless($item->payroll_period_id === $payroll->id, 404);

        $data = $request->validate([
            'deduction' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
        ]);

        $this->service->updateDeduction($item, (float) $data['deduction'], $request->user());

        return redirect()
            ->route('payroll.show', $payroll)
            ->with('success', __('ui.deduction_updated'));
    }

    public function approve(PayrollPeriod $payroll, Request $request): RedirectResponse
    {
        $this->authorize('approve', $payroll);

        $this->service->approve($payroll, $request->user());

        return redirect()
            ->route('payroll.show', $payroll)
            ->with('success', __('ui.period_approved'));
    }

    public function close(PayrollPeriod $payroll, Request $request): RedirectResponse
    {
        $this->authorize('close', $payroll);

        $expense = $this->service->close($payroll, $request->user());

        return redirect()
            ->route('expenses.index')
            ->with('success', __('ui.period_closed').' (#'.$expense->id.')');
    }

    private function staffPool(PayrollPeriod $period)
    {
        return User::where('is_active', true)
            ->where(function ($q) use ($period) {
                $q->whereHas('branches', fn ($b) => $b->where('branches.id', $period->branch_id))
                    ->orWhereHas('staffSchedules', fn ($s) => $s
                        ->where('schedules.branch_id', $period->branch_id)
                        ->where('status', 'completed'));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'commission_type', 'commission_rate', 'base_salary']);
    }

    private function editableBranches()
    {
        if (auth()->user()->hasRole('admin-cabang')) {
            return auth()->user()->branches()->where('is_active', true)->orderBy('name')->get();
        }

        return Branch::where('is_active', true)->orderBy('name')->get();
    }

    private function assertAllowedBranch(int $branchId): void
    {
        if (auth()->user()->hasRole('admin-cabang')) {
            $allowed = auth()->user()->branches()->pluck('branches.id')->contains($branchId);

            abort_unless($allowed, 403);
        }
    }

    private function assertNoOverlap(int $branchId, string $start, string $end): void
    {
        $overlap = PayrollPeriod::where('branch_id', $branchId)
            ->whereDate('period_start', '<=', $end)
            ->whereDate('period_end', '>=', $start)
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'period_start' => __('ui.period_overlapping'),
            ]);
        }
    }
}
