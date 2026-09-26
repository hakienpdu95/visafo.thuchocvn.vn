<?php

namespace Modules\Product\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\Product\Models\AgriFertilizer;

class AgriFertilizerPolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'compliance');
    }

    public function view(User $user, AgriFertilizer $agriFertilizer): bool
    {
        return ModuleAccess::view($user, 'compliance', $agriFertilizer);
    }
}
