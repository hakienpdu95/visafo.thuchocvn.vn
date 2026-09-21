<?php

namespace Modules\FoodInspection\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\FoodInspection\Models\FoodInspectionStep1Log;

class GetFoodInspectionStep1LogQuery implements QueryInterface
{
    public function __construct(public readonly FoodInspectionStep1Log $log) {}
}
