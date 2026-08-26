<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('customers.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        if (! $user->can('customers.view')) {
            return false;
        }

        return $this->withinBranch($user, $customer);
    }

    public function create(User $user): bool
    {
        return $user->can('customers.create');
    }

    public function update(User $user, Customer $customer): bool
    {
        if (! $user->can('customers.edit')) {
            return false;
        }

        return $this->withinBranch($user, $customer);
    }

    public function delete(User $user, Customer $customer): bool
    {
        if (! $user->can('customers.delete')) {
            return false;
        }

        return $this->withinBranch($user, $customer);
    }

    private function withinBranch(User $user, Customer $customer): bool
    {
        if ($user->hasRole('owner')) {
            return true;
        }

        return $user->branches()->pluck('branches.id')->contains($customer->branch_id);
    }
}
