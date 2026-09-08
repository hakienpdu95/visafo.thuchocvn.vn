<?php

namespace Modules\Product\Policies;

use App\Enums\RoleEnum;
use App\Models\User;
use Modules\Product\Models\ProductCompliance;

class ProductCompliancePolicy
{
    public function create(User $user): bool
    {
        return $user->can('product.manage') && $user->hasAnyRole(['super-admin', RoleEnum::ADMIN->value]);
    }

    public function delete(User $user, ProductCompliance $compliance): bool
    {
        return $user->can('product.manage') && $user->hasAnyRole(['super-admin', RoleEnum::ADMIN->value]);
    }
}
