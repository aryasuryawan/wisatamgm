<?php

namespace App\Http\Controllers\User;

use App\Domain\User\Services\UserService;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return view('users.index', [
            'users' => User::with(['branches', 'roles'])
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create', [
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
            'roles' => Role::where('guard_name', 'web')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $this->validated($request);
        $branchIds = $request->input('branches', []);
        $roleNames = $request->input('roles', []);

        $user = $this->users->createUser(
            $data,
            is_array($branchIds) ? array_map('intval', $branchIds) : [],
            is_array($roleNames) ? $roleNames : []
        );

        return redirect()
            ->route('users.edit', $user)
            ->with('success', __('ui.user_created'));
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', [
            'user' => $user->load(['branches', 'roles']),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
            'roles' => Role::where('guard_name', 'web')->orderBy('name')->get(),
            'assignedBranchIds' => $user->branches->modelKeys(),
            'assignedRoleNames' => $user->getRoleNames()->all(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $this->validated($request, $user->id);
        $branchIds = $request->input('branches', []);
        $roleNames = $request->input('roles', []);

        $this->users->updateUser(
            $user,
            $data,
            is_array($branchIds) ? array_map('intval', $branchIds) : [],
            is_array($roleNames) ? $roleNames : []
        );

        return redirect()
            ->route('users.edit', $user)
            ->with('success', __('ui.user_updated'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $this->users->deleteUser($user);

        return redirect()
            ->route('users.index')
            ->with('success', __('ui.user_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email' . ($ignoreId ? ",$ignoreId" : '')],
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => $ignoreId ? ['nullable', 'string', 'min:8', 'confirmed'] : ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['sometimes', 'boolean'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'commission_type' => ['nullable', 'in:per_pax,per_trip,none'],
            'commission_rate' => ['nullable', 'numeric', 'min:0'],
        ];

        return $request->validate($rules);
    }
}