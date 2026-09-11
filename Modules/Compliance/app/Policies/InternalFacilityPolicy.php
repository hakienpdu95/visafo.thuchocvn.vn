<?php

namespace Modules\Compliance\Policies;

use App\Models\User;
use Modules\Compliance\Models\InternalFacility;

class InternalFacilityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('compliance.view');
    }

    public function view(User $user, InternalFacility $internalFacility): bool
    {
        return $user->can('compliance.view');
    }

    public function create(User $user): bool
    {
        return $user->can('compliance.manage');
    }

    public function update(User $user, InternalFacility $internalFacility): bool
    {
        return $user->can('compliance.manage');
    }
}
