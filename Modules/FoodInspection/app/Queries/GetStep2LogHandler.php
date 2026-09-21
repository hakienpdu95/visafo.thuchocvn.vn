<?php

namespace Modules\FoodInspection\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\FoodInspection\Models\FoodInspectionStep2Log;

class GetStep2LogHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): FoodInspectionStep2Log
    {
        /** @var GetStep2LogQuery $query */
        return $query->log->load(['details', 'inspector:id,name']);
    }
}
