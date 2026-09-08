<?php

namespace Modules\Warehouse\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\Warehouse\Models\InboundReceipt;

class GetInboundReceiptQuery implements QueryInterface
{
    public function __construct(
        public readonly InboundReceipt $inboundReceipt,
    ) {}
}
