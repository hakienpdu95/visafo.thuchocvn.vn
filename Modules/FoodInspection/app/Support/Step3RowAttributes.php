<?php

namespace Modules\FoodInspection\Support;

use Modules\FoodInspection\Data\Requests\Step3DetailData;

class Step3RowAttributes
{
    public static function from(Step3DetailData $d, int $lineNo): array
    {
        return [
            'step2_detail_id' => $d->step2_detail_id,
            'menu_dish_id'    => $d->menu_dish_id,
            'line_no'         => $lineNo,
            'meal_time'       => $d->meal_time,
            'dish_name'       => $d->dish_name,
            'quantity'        => $d->quantity,
            'portion_time'    => $d->portion_time,
            'eat_time'        => $d->eat_time,
            'equipment_used'  => $d->equipment_used,
            'sensory_eval'    => $d->sensory_eval,
            'action_taken'    => $d->isFailed() ? $d->action_taken : null,
        ];
    }
}
