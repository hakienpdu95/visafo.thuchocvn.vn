<?php

namespace Modules\Product\Policies;

use App\Models\User;
use Modules\Product\Models\AgriSeed;

class AgriSeedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('compliance.view');
    }

    public function view(User $user, AgriSeed $agriSeed): bool
    {
        return $user->can('compliance.view');
    }

    public function update(User $user, AgriSeed $agriSeed): bool
    {
        return $user->can('compliance.manage');
    }
}
