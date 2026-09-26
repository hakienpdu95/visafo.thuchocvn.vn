<?php

namespace Modules\Product\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\Product\Models\AgriSeed;

class AgriSeedPolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'compliance');
    }

    public function view(User $user, AgriSeed $agriSeed): bool
    {
        return ModuleAccess::view($user, 'compliance', $agriSeed);
    }

    public function update(User $user, AgriSeed $agriSeed): bool
    {
        return ModuleAccess::update($user, 'compliance', $agriSeed);
    }
}
