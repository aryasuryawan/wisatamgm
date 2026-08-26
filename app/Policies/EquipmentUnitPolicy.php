<?php

namespace App\Policies;

use App\Models\EquipmentUnit;
use App\Models\User;

class EquipmentUnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('equipment.view');
    }

    public function create(User $user): bool
    {
        return $user->can('equipment.create');
    }

    public function update(User $user, EquipmentUnit $unit): bool
    {
        return $user->can('equipment.edit');
    }

    public function delete(User $user, EquipmentUnit $unit): bool
    {
        return $user->can('equipment.delete');
    }
}
