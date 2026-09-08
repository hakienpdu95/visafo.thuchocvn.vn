<?php

namespace Modules\Recall\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Recall\Models\ProductRecall;

class GetProductRecallHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): ProductRecall
    {
        /** @var GetProductRecallQuery $query */
        $recall = $query->recall;

        $recall->load(['product', 'batch', 'initiator']);

        return $recall;
    }
}
