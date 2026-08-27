<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    public function manage(User $user): bool
    {
        return $user->can('settings.edit');
    }
}
