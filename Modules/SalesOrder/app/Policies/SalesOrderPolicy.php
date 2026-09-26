<?php

namespace Modules\SalesOrder\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\SalesOrder\Models\SalesOrder;

class SalesOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'sales_order');
    }

    public function view(User $user, SalesOrder $salesOrder): bool
    {
        return ModuleAccess::view($user, 'sales_order', $salesOrder);
    }

    public function print(User $user, SalesOrder $salesOrder): bool
    {
        return ModuleAccess::update($user, 'sales_order', $salesOrder);
    }

    public function create(User $user): bool
    {
        return ModuleAccess::create($user, 'sales_order');
    }
}
