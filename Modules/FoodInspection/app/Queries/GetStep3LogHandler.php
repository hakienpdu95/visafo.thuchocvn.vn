<?php

namespace Modules\FoodInspection\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\FoodInspection\Models\FoodInspectionStep3Log;

class GetStep3LogHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): FoodInspectionStep3Log
    {
        /** @var GetStep3LogQuery $query */
        return $query->log->load(['details', 'inspector:id,name']);
    }
}
