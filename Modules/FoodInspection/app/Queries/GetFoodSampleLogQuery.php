<?php

namespace Modules\FoodInspection\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\FoodInspection\Models\FoodSampleLog;

class GetFoodSampleLogQuery implements QueryInterface
{
    public function __construct(public readonly FoodSampleLog $log) {}
}
