<?php

namespace Modules\Vendor\Policies;

use App\Models\User;
use Modules\Vendor\Models\Vendor;

class VendorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('vendor.view');
    }

    public function view(User $user, Vendor $vendor): bool
    {
        return $user->can('vendor.view');
    }

    public function create(User $user): bool
    {
        return $user->can('vendor.manage');
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $user->can('vendor.manage');
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return $user->can('vendor.manage');
    }
}
