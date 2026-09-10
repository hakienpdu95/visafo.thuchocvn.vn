<?php

namespace Modules\Product\Policies;

use App\Enums\RoleEnum;
use App\Models\User;
use Modules\Product\Models\Category;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('product.view');
    }

    public function view(User $user, Category $category): bool
    {
        return $user->can('product.view');
    }

    public function create(User $user): bool
    {
        return $user->can('product.manage') && $user->hasRole(RoleEnum::ADMIN->value);
    }

    public function update(User $user, Category $category): bool
    {
        return $user->can('product.manage');
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->can('product.manage') && $user->hasRole(RoleEnum::ADMIN->value);
    }

    public function editCode(User $user): bool
    {
        return $user->hasRole(RoleEnum::ADMIN->value);
    }
}
