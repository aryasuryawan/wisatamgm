<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $model): bool
    {
        if (!$user->can('users.edit')) {
            return false;
        }

        return $user->id !== $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        if (!$user->can('users.delete')) {
            return false;
        }

        return $user->id !== $model->id;
    }
}