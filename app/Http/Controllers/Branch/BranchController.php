<?php

namespace App\Http\Controllers\Branch;

use App\Domain\Branch\Services\BranchService;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function __construct(private readonly BranchService $branches) {}

    public function index(): View
    {
        $this->authorize('viewAny', Branch::class);

        return view('branches.index', [
            'branches' => Branch::withCount('users')->orderBy('name')->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Branch::class);

        return view('branches.create', [
            'users' => User::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Branch::class);

        $data = $this->validated($request);
        $userIds = $request->input('users', []);

        $branch = $this->branches->createBranch($data, is_array($userIds) ? array_map(intval(...), $userIds) : []);

        return redirect()
            ->route('branches.edit', $branch)
            ->with('success', __('ui.branch_created'));
    }

    public function edit(Branch $branch): View
    {
        $this->authorize('update', $branch);

        return view('branches.edit', [
            'branch' => $branch,
            'users' => User::where('is_active', true)->orderBy('name')->get(),
            'assignedUserIds' => $branch->users()->pluck('users.id')->all(),
        ]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $this->authorize('update', $branch);

        $data = $this->validated($request, $branch->id);
        $userIds = $request->input('users', []);

        $this->branches->updateBranch(
            $branch,
            $data,
            is_array($userIds) ? array_map(intval(...), $userIds) : []
        );

        return redirect()
            ->route('branches.edit', $branch)
            ->with('success', __('ui.branch_updated'));
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->authorize('delete', $branch);

        $this->branches->deleteBranch($branch);

        return redirect()
            ->route('branches.index')
            ->with('success', __('ui.branch_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'in:tulambenscuba,scubago,lainnya'],
            'domain' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:32'],
            'pic_user_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
