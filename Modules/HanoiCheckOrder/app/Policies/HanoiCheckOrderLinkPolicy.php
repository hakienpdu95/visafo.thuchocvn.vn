<?php

namespace Modules\HanoiCheckOrder\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\SalesOrder\Models\SalesOrder;

class HanoiCheckOrderLinkPolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'sales_order');
    }

    public function manageTransporters(User $user): bool
    {
        return ModuleAccess::update($user, 'sales_order');
    }

    public function link(User $user, SalesOrder $salesOrder): bool
    {
        return ModuleAccess::update($user, 'sales_order', $salesOrder) && ! $salesOrder->trashed();
    }
}
