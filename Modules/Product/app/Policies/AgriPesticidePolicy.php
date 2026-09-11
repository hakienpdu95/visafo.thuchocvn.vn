<?php

namespace Modules\Product\Policies;

use App\Models\User;
use Modules\Product\Models\AgriPesticide;

class AgriPesticidePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('compliance.view');
    }

    public function view(User $user, AgriPesticide $agriPesticide): bool
    {
        return $user->can('compliance.view');
    }

    public function update(User $user, AgriPesticide $agriPesticide): bool
    {
        return $user->can('compliance.manage');
    }
}
