<?php

namespace Modules\Product\Policies;

use App\Models\User;
use Modules\Product\Models\FarmingSource;

class FarmingSourcePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('compliance.view');
    }

    public function view(User $user, FarmingSource $farmingSource): bool
    {
        return $user->can('compliance.view');
    }

    public function create(User $user): bool
    {
        return $user->can('compliance.manage');
    }

    public function update(User $user, FarmingSource $farmingSource): bool
    {
        return $user->can('compliance.manage');
    }
}
