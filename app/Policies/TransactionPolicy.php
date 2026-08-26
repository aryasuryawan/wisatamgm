<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('transactions.view');
    }

    public function view(User $user, Transaction $transaction): bool
    {
        if (! $user->can('transactions.view')) {
            return false;
        }

        return $this->withinBranch($user, $transaction);
    }

    public function create(User $user): bool
    {
        return $user->can('transactions.create');
    }

    public function void(User $user, Transaction $transaction): bool
    {
        if (! $user->can('transactions.void')) {
            return false;
        }

        return $this->withinBranch($user, $transaction);
    }

    public function pay(User $user, Transaction $transaction): bool
    {
        if (! $user->can('transactions.create')) {
            return false;
        }

        return $this->withinBranch($user, $transaction);
    }

    private function withinBranch(User $user, Transaction $transaction): bool
    {
        if ($user->hasRole('owner')) {
            return true;
        }

        return $user->branches()->pluck('branches.id')->contains($transaction->branch_id);
    }
}
