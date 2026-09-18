<?php

namespace Modules\GoodsReceipt\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\GoodsReceipt\Models\GoodsReceipt;

class GetGoodsReceiptHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): GoodsReceipt
    {
        /** @var GetGoodsReceiptQuery $query */
        $goodsReceipt = $query->goodsReceipt;

        $goodsReceipt->load([
            'vendor',
            'importedBy',
            'items.product',
            'batches',
        ]);

        return $goodsReceipt;
    }
}
