<?php

namespace Modules\Product\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\Product\Models\Product;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'product');
    }

    public function view(User $user, Product $product): bool
    {
        return ModuleAccess::view($user, 'product', $product);
    }

    public function create(User $user): bool
    {
        return ModuleAccess::create($user, 'product');
    }

    public function update(User $user, Product $product): bool
    {
        return ModuleAccess::update($user, 'product', $product);
    }

    public function delete(User $user, Product $product): bool
    {
        return ModuleAccess::delete($user, 'product', $product);
    }
}
