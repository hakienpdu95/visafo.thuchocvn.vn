<?php

namespace Modules\FoodInspection\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\FoodInspection\Models\FoodInspectionStep3Log;

class GetStep3LogQuery implements QueryInterface
{
    public function __construct(public readonly FoodInspectionStep3Log $log) {}
}
