<?php

namespace Modules\Compliance\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\Compliance\Models\InternalFacility;

class InternalFacilityPolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'compliance');
    }

    public function view(User $user, InternalFacility $internalFacility): bool
    {
        return ModuleAccess::view($user, 'compliance', $internalFacility);
    }

    public function create(User $user): bool
    {
        return ModuleAccess::create($user, 'compliance');
    }

    public function update(User $user, InternalFacility $internalFacility): bool
    {
        return ModuleAccess::update($user, 'compliance', $internalFacility);
    }
}
