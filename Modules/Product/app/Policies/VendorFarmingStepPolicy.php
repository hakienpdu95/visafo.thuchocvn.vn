<?php

namespace Modules\Product\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\Product\Models\VendorFarmingStep;

class VendorFarmingStepPolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'compliance');
    }

    public function view(User $user, VendorFarmingStep $vendorFarmingStep): bool
    {
        return ModuleAccess::view($user, 'compliance', $vendorFarmingStep);
    }

    public function create(User $user): bool
    {
        return ModuleAccess::create($user, 'compliance');
    }

    public function update(User $user, VendorFarmingStep $vendorFarmingStep): bool
    {
        return ModuleAccess::update($user, 'compliance', $vendorFarmingStep);
    }

    public function delete(User $user, VendorFarmingStep $vendorFarmingStep): bool
    {
        return ModuleAccess::delete($user, 'compliance', $vendorFarmingStep);
    }
}
