<?php

namespace App\Policies;

use App\Models\ProductCategory;
use App\Models\User;

class ProductCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('products.view');
    }

    public function view(User $user, ProductCategory $productCategory): bool
    {
        return $user->can('products.view');
    }

    public function create(User $user): bool
    {
        return $user->can('products.create');
    }

    public function update(User $user, ProductCategory $productCategory): bool
    {
        return $user->can('products.edit');
    }

    public function delete(User $user, ProductCategory $productCategory): bool
    {
        return $user->can('products.delete');
    }
}
