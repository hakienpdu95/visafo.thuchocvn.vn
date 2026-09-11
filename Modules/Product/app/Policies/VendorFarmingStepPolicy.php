<?php

namespace Modules\Product\Policies;

use App\Models\User;
use Modules\Product\Models\VendorFarmingStep;

class VendorFarmingStepPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('compliance.view');
    }

    public function view(User $user, VendorFarmingStep $vendorFarmingStep): bool
    {
        return $user->can('compliance.view');
    }

    public function create(User $user): bool
    {
        return $user->can('compliance.manage');
    }

    public function update(User $user, VendorFarmingStep $vendorFarmingStep): bool
    {
        return $user->can('compliance.manage');
    }

    public function delete(User $user, VendorFarmingStep $vendorFarmingStep): bool
    {
        return $user->can('compliance.manage');
    }
}
