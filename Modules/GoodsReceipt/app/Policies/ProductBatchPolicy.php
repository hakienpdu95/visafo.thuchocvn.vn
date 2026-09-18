<?php

namespace Modules\GoodsReceipt\Policies;

use App\Models\User;
use Modules\GoodsReceipt\Models\ProductBatch;

class ProductBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('goods_receipt.view');
    }

    public function view(User $user, ProductBatch $productBatch): bool
    {
        return $user->can('goods_receipt.view');
    }

    public function update(User $user, ProductBatch $productBatch): bool
    {
        return $user->can('goods_receipt.manage');
    }
}
