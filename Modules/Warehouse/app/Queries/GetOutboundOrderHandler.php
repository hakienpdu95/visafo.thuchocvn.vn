<?php

namespace Modules\Warehouse\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Warehouse\Models\OutboundOrder;

class GetOutboundOrderHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): OutboundOrder
    {
        /** @var GetOutboundOrderQuery $query */
        $order = $query->order;

        $order->load(['pickedBatches.product', 'pickedBatches.batch']);

        return $order;
    }
}
