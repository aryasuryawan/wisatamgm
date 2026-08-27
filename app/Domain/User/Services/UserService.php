<?php

namespace App\Domain\User\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserService
{
    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $branchIds
     * @param  list<string>  $roleNames
     */
    public function createUser(array $data, array $branchIds = [], array $roleNames = []): User
    {
        return DB::transaction(function () use ($data, $branchIds, $roleNames) {
            /** @var User $user */
            $user = User::create($data);

            if ($branchIds) {
                $user->branches()->sync($branchIds);
            }

            if ($roleNames) {
                $user->syncRoles($roleNames);
            }

            AuditLogger::log('user.created', $user, null, $this->snapshot($user, $branchIds, $roleNames));

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $branchIds
     * @param  list<string>  $roleNames
     */
    public function updateUser(User $user, array $data, array $branchIds = [], array $roleNames = []): void
    {
        DB::transaction(function () use ($user, $data, $branchIds, $roleNames) {
            $before = $this->snapshot($user, $user->branches->modelKeys(), $user->getRoleNames()->all());

            $user->fill($data)->save();

            if ($branchIds) {
                $user->branches()->sync($branchIds);
            }

            if ($roleNames) {
                $user->syncRoles($roleNames);
            }

            AuditLogger::log('user.updated', $user, $before, $this->snapshot($user, $branchIds, $roleNames));
        });
    }

    public function deleteUser(User $user): void
    {
        DB::transaction(function () use ($user) {
            $before = $this->snapshot($user, $user->branches->modelKeys(), $user->getRoleNames()->all());

            $user->branches()->detach();
            $user->roles()->detach();
            $user->delete();

            AuditLogger::log('user.deleted', $user, $before, null);
        });
    }

    /**
     * @param  list<int>  $branchIds
     * @param  list<string>  $roleNames
     * @return array<string, mixed>
     */
    private function snapshot(User $user, array $branchIds, array $roleNames): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'is_active' => $user->is_active,
            'base_salary' => $user->base_salary,
            'commission_type' => $user->commission_type,
            'commission_rate' => $user->commission_rate,
            'branch_ids' => $branchIds,
            'roles' => $roleNames,
        ];
    }
}