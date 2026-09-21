<?php

namespace Modules\FoodInspection\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\FoodInspection\Models\FoodInspectionStep1Log;

class GetFoodInspectionStep1LogHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): FoodInspectionStep1Log
    {
        /** @var GetFoodInspectionStep1LogQuery $query */
        return $query->log->load(['details', 'inspector:id,name', 'customer:id,name']);
    }
}
