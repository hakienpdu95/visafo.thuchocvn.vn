<?php

namespace Modules\SalesPackage\Policies;

use App\Models\User;
use Modules\SalesPackage\Enums\SalesPackageStatus;
use Modules\SalesPackage\Models\SalesPackage;

class SalesPackagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('customer.view');
    }

    public function view(User $user, SalesPackage $package): bool
    {
        return $user->can('customer.view');
    }

    public function create(User $user): bool
    {
        return $user->can('customer.manage');
    }

    public function update(User $user, SalesPackage $package): bool
    {
        return $user->can('customer.manage') && $package->status === SalesPackageStatus::Draft;
    }

    public function delete(User $user, SalesPackage $package): bool
    {
        return $user->can('customer.manage') && $package->status === SalesPackageStatus::Draft;
    }

    public function updateStatus(User $user, SalesPackage $package): bool
    {
        return $user->can('customer.manage');
    }
}
