<?php

namespace App\Policies;

use App\Models\MarketingCampaign;
use App\Models\User;

class MarketingCampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('expenses.view') || $user->can('discounts.view');
    }

    public function create(User $user): bool
    {
        return $user->can('expenses.create') || $user->can('discounts.create');
    }

    public function update(User $user, MarketingCampaign $campaign): bool
    {
        return $user->can('expenses.edit') || $user->can('discounts.edit');
    }

    public function delete(User $user, MarketingCampaign $campaign): bool
    {
        return $user->can('expenses.delete') || $user->can('discounts.delete');
    }
}
