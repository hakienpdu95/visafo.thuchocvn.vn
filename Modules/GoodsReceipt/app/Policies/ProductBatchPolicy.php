<?php

namespace Modules\GoodsReceipt\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\GoodsReceipt\Models\ProductBatch;

class ProductBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'goods_receipt');
    }

    public function view(User $user, ProductBatch $productBatch): bool
    {
        return ModuleAccess::view($user, 'goods_receipt', $productBatch);
    }

    public function update(User $user, ProductBatch $productBatch): bool
    {
        return ModuleAccess::update($user, 'goods_receipt', $productBatch);
    }
}
