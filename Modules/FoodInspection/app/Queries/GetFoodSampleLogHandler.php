<?php

namespace Modules\FoodInspection\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\FoodInspection\Models\FoodSampleLog;

class GetFoodSampleLogHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): FoodSampleLog
    {
        /** @var GetFoodSampleLogQuery $query */
        return $query->log->load(['details', 'creator:id,name']);
    }
}
