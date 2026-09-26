<?php

namespace Modules\Product\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\Product\Models\AgriPesticide;

class AgriPesticidePolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'compliance');
    }

    public function view(User $user, AgriPesticide $agriPesticide): bool
    {
        return ModuleAccess::view($user, 'compliance', $agriPesticide);
    }

    public function update(User $user, AgriPesticide $agriPesticide): bool
    {
        return ModuleAccess::update($user, 'compliance', $agriPesticide);
    }
}
