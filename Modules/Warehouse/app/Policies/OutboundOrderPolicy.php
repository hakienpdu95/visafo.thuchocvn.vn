<?php

namespace Modules\Warehouse\Policies;

use App\Models\User;
use Modules\Warehouse\Models\OutboundOrder;

class OutboundOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('warehouse.view');
    }

    public function view(User $user, OutboundOrder $order): bool
    {
        return $user->can('warehouse.view');
    }

    public function create(User $user): bool
    {
        return $user->can('warehouse.manage');
    }

    public function update(User $user, OutboundOrder $order): bool
    {
        return $user->can('warehouse.manage');
    }
}
