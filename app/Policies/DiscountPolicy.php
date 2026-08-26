<?php

namespace App\Policies;

use App\Models\Discount;
use App\Models\User;

class DiscountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('discounts.view');
    }

    public function create(User $user): bool
    {
        return $user->can('discounts.create');
    }

    public function update(User $user, Discount $discount): bool
    {
        return $user->can('discounts.edit');
    }

    public function delete(User $user, Discount $discount): bool
    {
        if (! $user->can('discounts.delete')) {
            return false;
        }

        return $discount->usages()->doesntExist();
    }
}
