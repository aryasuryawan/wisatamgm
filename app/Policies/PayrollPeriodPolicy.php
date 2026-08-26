<?php

namespace App\Policies;

use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\User;

class PayrollPeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payroll.view');
    }

    public function create(User $user): bool
    {
        return $user->can('payroll.create');
    }

    public function update(User $user, PayrollPeriod $period): bool
    {
        return $period->status === 'draft' && $user->can('payroll.edit');
    }

    public function approve(User $user, PayrollPeriod $period): bool
    {
        return $period->status === 'draft' && $user->can('payroll.approve');
    }

    public function close(User $user, PayrollPeriod $period): bool
    {
        return $period->status === 'approved' && $user->can('payroll.approve');
    }
}
