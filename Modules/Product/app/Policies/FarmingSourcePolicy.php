<?php

namespace Modules\Product\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\Product\Models\FarmingSource;

class FarmingSourcePolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'compliance');
    }

    public function view(User $user, FarmingSource $farmingSource): bool
    {
        return ModuleAccess::view($user, 'compliance', $farmingSource);
    }

    public function create(User $user): bool
    {
        return ModuleAccess::create($user, 'compliance');
    }

    public function update(User $user, FarmingSource $farmingSource): bool
    {
        return ModuleAccess::update($user, 'compliance', $farmingSource);
    }
}
