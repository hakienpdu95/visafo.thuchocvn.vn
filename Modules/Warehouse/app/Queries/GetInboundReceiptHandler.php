<?php

namespace Modules\Warehouse\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Warehouse\Models\InboundReceipt;

class GetInboundReceiptHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): InboundReceipt
    {
        /** @var GetInboundReceiptQuery $query */
        $inboundReceipt = $query->inboundReceipt;

        $inboundReceipt->load([
            'vendor',
            'documents',
            'batches' => fn ($q) => $q->with('product')->withCount('tags')->latest('created_at'),
        ]);

        return $inboundReceipt;
    }
}
