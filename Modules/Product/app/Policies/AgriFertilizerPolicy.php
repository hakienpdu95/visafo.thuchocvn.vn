<?php

namespace Modules\Product\Policies;

use App\Models\User;
use Modules\Product\Models\AgriFertilizer;

class AgriFertilizerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('compliance.view');
    }

    public function view(User $user, AgriFertilizer $agriFertilizer): bool
    {
        return $user->can('compliance.view');
    }
}
