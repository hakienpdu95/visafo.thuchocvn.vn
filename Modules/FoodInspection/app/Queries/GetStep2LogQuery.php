<?php

namespace Modules\FoodInspection\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\FoodInspection\Models\FoodInspectionStep2Log;

class GetStep2LogQuery implements QueryInterface
{
    public function __construct(public readonly FoodInspectionStep2Log $log) {}
}
