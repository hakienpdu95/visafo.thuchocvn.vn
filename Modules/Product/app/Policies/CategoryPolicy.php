<?php

namespace Modules\Product\Policies;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\Product\Models\Category;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'product');
    }

    public function view(User $user, Category $category): bool
    {
        return ModuleAccess::view($user, 'product', $category);
    }

    public function create(User $user): bool
    {
        return ModuleAccess::create($user, 'product') && $user->hasRole(RoleEnum::ADMIN->value);
    }

    public function update(User $user, Category $category): bool
    {
        return ModuleAccess::update($user, 'product', $category);
    }

    public function delete(User $user, Category $category): bool
    {
        return ModuleAccess::delete($user, 'product', $category) && $user->hasRole(RoleEnum::ADMIN->value);
    }

    public function editCode(User $user): bool
    {
        return $user->hasRole(RoleEnum::ADMIN->value);
    }
}
