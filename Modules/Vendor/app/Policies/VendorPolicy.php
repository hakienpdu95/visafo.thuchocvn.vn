<?php

namespace Modules\Vendor\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\Vendor\Models\Vendor;

class VendorPolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'vendor');
    }

    public function view(User $user, Vendor $vendor): bool
    {
        return ModuleAccess::view($user, 'vendor', $vendor);
    }

    public function create(User $user): bool
    {
        return ModuleAccess::create($user, 'vendor');
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return ModuleAccess::update($user, 'vendor', $vendor);
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return ModuleAccess::delete($user, 'vendor', $vendor);
    }
}
