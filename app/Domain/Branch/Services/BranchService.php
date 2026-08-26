<?php

namespace App\Domain\Branch\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class BranchService
{
    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $userIds
     */
    public function createBranch(array $data, array $userIds = []): Branch
    {
        return DB::transaction(function () use ($data, $userIds) {
            /** @var Branch $branch */
            $branch = Branch::create($data);
            $branch->users()->sync($userIds);

            AuditLogger::log('branch.created', $branch, null, $this->snapshot($branch, $userIds));

            return $branch;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $userIds
     */
    public function updateBranch(Branch $branch, array $data, array $userIds = []): void
    {
        DB::transaction(function () use ($branch, $data, $userIds) {
            $before = $this->snapshot($branch, $branch->users->modelKeys());

            $branch->fill($data)->save();
            $branch->users()->sync($userIds);

            AuditLogger::log('branch.updated', $branch, $before, $this->snapshot($branch, $userIds));
        });
    }

    public function deleteBranch(Branch $branch): void
    {
        DB::transaction(function () use ($branch) {
            $before = $this->snapshot($branch, $branch->users->modelKeys());

            $branch->users()->detach();
            $branch->delete();

            AuditLogger::log('branch.deleted', $branch, $before, null);
        });
    }

    /**
     * @param  list<int>  $userIds
     * @return array<string, mixed>
     */
    private function snapshot(Branch $branch, array $userIds): array
    {
        return [
            'name' => $branch->name,
            'brand' => $branch->brand,
            'domain' => $branch->domain,
            'address' => $branch->address,
            'phone' => $branch->phone,
            'pic_user_id' => $branch->pic_user_id,
            'is_active' => $branch->is_active,
            'user_ids' => $userIds,
        ];
    }
}
