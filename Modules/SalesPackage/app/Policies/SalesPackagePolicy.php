<?php

namespace Modules\SalesPackage\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\SalesPackage\Enums\SalesPackageStatus;
use Modules\SalesPackage\Models\SalesPackage;

class SalesPackagePolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'customer');
    }

    public function view(User $user, SalesPackage $package): bool
    {
        return ModuleAccess::view($user, 'customer', $package);
    }

    public function create(User $user): bool
    {
        return ModuleAccess::create($user, 'customer');
    }

    public function update(User $user, SalesPackage $package): bool
    {
        return ModuleAccess::update($user, 'customer', $package) && $package->status === SalesPackageStatus::Draft;
    }

    public function delete(User $user, SalesPackage $package): bool
    {
        return ModuleAccess::delete($user, 'customer', $package) && $package->status === SalesPackageStatus::Draft;
    }

    public function updateStatus(User $user, SalesPackage $package): bool
    {
        return ModuleAccess::update($user, 'customer', $package);
    }
}
