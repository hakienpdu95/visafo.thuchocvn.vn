<?php

namespace Modules\Warehouse\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\Warehouse\Models\OutboundOrder;

class GetOutboundOrderQuery implements QueryInterface
{
    public function __construct(
        public readonly OutboundOrder $order,
    ) {}
}
