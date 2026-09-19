<?php

namespace Modules\SalesOrder\Policies;

use App\Models\User;
use Modules\SalesOrder\Models\SalesOrder;

class SalesOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales_order.view');
    }

    public function view(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales_order.view');
    }

    public function print(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales_order.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('sales_order.manage');
    }
}
