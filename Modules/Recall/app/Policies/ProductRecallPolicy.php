<?php

namespace Modules\Recall\Policies;

use App\Enums\RoleEnum;
use App\Models\User;
use Modules\Recall\Models\ProductRecall;

class ProductRecallPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('recall.view');
    }

    public function view(User $user, ProductRecall $recall): bool
    {
        return $user->can('recall.view');
    }

    public function create(User $user): bool
    {
        return $user->can('recall.manage') && $user->hasAnyRole(['super-admin', RoleEnum::ADMIN->value]);
    }

    public function update(User $user, ProductRecall $recall): bool
    {
        return $user->can('recall.manage') && $user->hasAnyRole(['super-admin', RoleEnum::ADMIN->value]);
    }
}
