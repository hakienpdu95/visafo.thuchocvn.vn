<?php

namespace Modules\GoodsReceipt\Policies;

use App\Models\User;
use Modules\GoodsReceipt\Models\GoodsReceipt;

class GoodsReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('goods_receipt.view');
    }

    public function view(User $user, GoodsReceipt $goodsReceipt): bool
    {
        return $user->can('goods_receipt.view');
    }

    public function create(User $user): bool
    {
        return $user->can('goods_receipt.manage');
    }

    public function update(User $user, GoodsReceipt $goodsReceipt): bool
    {
        return $user->can('goods_receipt.manage');
    }
}
