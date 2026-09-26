<?php

namespace Modules\GoodsReceipt\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\GoodsReceipt\Models\GoodsReceipt;

class GoodsReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'goods_receipt');
    }

    public function view(User $user, GoodsReceipt $goodsReceipt): bool
    {
        return ModuleAccess::view($user, 'goods_receipt', $goodsReceipt);
    }

    public function create(User $user): bool
    {
        return ModuleAccess::create($user, 'goods_receipt');
    }

    public function update(User $user, GoodsReceipt $goodsReceipt): bool
    {
        return ModuleAccess::update($user, 'goods_receipt', $goodsReceipt);
    }
}
