<?php

namespace Modules\Contract\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;

class VendorComplianceRequirementPolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'contract');
    }

    public function create(User $user): bool
    {
        return $user->can('contract.view_all') && ModuleAccess::create($user, 'contract');
    }

    public function update(User $user): bool
    {
        return $user->can('contract.view_all') && $user->can('contract.update');
    }

    public function delete(User $user): bool
    {
        return $user->can('contract.view_all') && $user->can('contract.delete');
    }
}
