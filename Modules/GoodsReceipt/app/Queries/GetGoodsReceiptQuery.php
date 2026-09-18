<?php

namespace Modules\GoodsReceipt\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\GoodsReceipt\Models\GoodsReceipt;

class GetGoodsReceiptQuery implements QueryInterface
{
    public function __construct(
        public readonly GoodsReceipt $goodsReceipt,
    ) {}
}
