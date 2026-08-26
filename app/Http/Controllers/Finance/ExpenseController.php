<?php

namespace App\Http\Controllers\Finance;

use App\Domain\Finance\Services\ExpenseService;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\MarketingCampaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(private ExpenseService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Expense::class);

        $query = Expense::with(['category', 'branch', 'campaign'])
            ->orderByDesc('expense_date')
            ->orderByDesc('id');

        $query = $this->applyBranchScope($query);

        if ($categoryId = $request->input('category_id')) {
            $query->where('expense_category_id', $categoryId);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_until')) {
            $query->whereDate('expense_date', '<=', $request->input('date_until'));
        }

        return view('finance.expenses.index', [
            'expenses' => $query->paginate(20)->withQueryString(),
            'branches' => $this->availableBranches(),
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'totalAmount' => (clone $query)->sum('amount'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Expense::class);

        return view('finance.expenses.create', [
            'branches' => $this->editableBranches(),
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'campaigns' => MarketingCampaign::orderBy('name')->get(),
        ]);
    }

    public function show(Expense $expense): View
    {
        $this->authorize('view', $expense);

        return view('finance.expenses.show', [
            'expense' => $expense,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Expense::class);

        $data = $this->validated($request);
        $data['branch_id'] = $this->assertAllowedBranch((int) $data['branch_id']);
        $data['proof_path'] = $this->storeProof($request);

        $expense = $this->service->create($request->user(), $data);

        return redirect()
            ->route('expenses.index')
            ->with('success', __('ui.expense_created'));
    }

    public function edit(Expense $expense): View
    {
        $this->authorize('update', $expense);

        return view('finance.expenses.edit', [
            'expense' => $expense,
            'branches' => $this->editableBranches(),
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'campaigns' => MarketingCampaign::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorize('update', $expense);

        $data = $this->validated($request);
        $data['branch_id'] = $this->assertAllowedBranch((int) $data['branch_id']);

        if ($proofPath = $this->storeProof($request)) {
            if ($expense->proof_path) {
                \Storage::disk('public')->delete($expense->proof_path);
            }
            $data['proof_path'] = $proofPath;
        }

        $this->service->update($expense, $request->user(), $data);

        return redirect()
            ->route('expenses.index')
            ->with('success', __('ui.expense_updated'));
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);

        $this->service->delete($expense, request()->user());

        return redirect()
            ->route('expenses.index')
            ->with('success', __('ui.expense_deleted'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'expense_date' => ['required', 'date'],
            'marketing_campaign_id' => [
                'nullable',
                Rule::exists('marketing_campaigns', 'id'),
            ],
            'proof' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp,application/pdf', 'max:2048'],
        ]);
    }

    private function storeProof(Request $request): ?string
    {
        $file = $request->file('proof');

        if (! $file || $file->getError() !== UPLOAD_ERR_OK || ! $file->getPathname()) {
            return null;
        }

        $name = 'proofs/'.Str::random(40).'.'.$file->getClientOriginalExtension();
        $stream = fopen($file->getPathname(), 'r');
        \Storage::disk('public')->put($name, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        return $name;
    }

    private function applyBranchScope($query)
    {
        if (! auth()->user()->hasRole('admin-cabang')) {
            return $query;
        }

        $branchIds = auth()->user()->branches()->pluck('branches.id');

        return $query->whereIn('branch_id', $branchIds);
    }

    private function availableBranches()
    {
        if (auth()->user()->hasRole('admin-cabang')) {
            return auth()->user()->branches()->orderBy('name')->get();
        }

        return Branch::orderBy('name')->get();
    }

    private function editableBranches()
    {
        if (auth()->user()->hasRole('admin-cabang')) {
            return auth()->user()->branches()->where('is_active', true)->orderBy('name')->get();
        }

        return Branch::where('is_active', true)->orderBy('name')->get();
    }

    private function assertAllowedBranch(int $branchId): int
    {
        if (auth()->user()->hasRole('admin-cabang')) {
            $allowed = auth()->user()->branches()->pluck('branches.id')->contains($branchId);

            abort_unless($allowed, 403);
        }

        return $branchId;
    }
}
