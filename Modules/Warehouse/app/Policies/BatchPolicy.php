<?php

namespace Modules\Warehouse\Policies;

use App\Enums\RoleEnum;
use App\Models\User;
use Modules\Warehouse\Models\Batch;

class BatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('warehouse.view');
    }

    public function view(User $user, Batch $batch): bool
    {
        return $user->can('warehouse.view');
    }

    public function update(User $user, Batch $batch): bool
    {
        return $user->can('warehouse.manage');
    }

    public function recall(User $user, Batch $batch): bool
    {
        return $user->can('warehouse.manage') && $user->hasAnyRole(['super-admin', RoleEnum::ADMIN->value]);
    }
}
