<?php

namespace Modules\Warehouse\Policies;

use App\Models\User;
use Modules\Warehouse\Models\InboundReceipt;

class InboundReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('warehouse.view');
    }

    public function view(User $user, InboundReceipt $inboundReceipt): bool
    {
        return $user->can('warehouse.view');
    }

    public function create(User $user): bool
    {
        return $user->can('warehouse.manage');
    }

    public function update(User $user, InboundReceipt $inboundReceipt): bool
    {
        return $user->can('warehouse.manage');
    }

    public function delete(User $user, InboundReceipt $inboundReceipt): bool
    {
        return $user->can('warehouse.manage');
    }
}
