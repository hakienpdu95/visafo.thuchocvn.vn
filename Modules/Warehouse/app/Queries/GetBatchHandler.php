<?php

namespace Modules\Warehouse\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Warehouse\Models\Batch;

class GetBatchHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Batch
    {
        /** @var GetBatchQuery $query */
        $batch = $query->batch;

        $batch->load(['product', 'vendor', 'inboundReceipt', 'documents']);

        return $batch;
    }
}
