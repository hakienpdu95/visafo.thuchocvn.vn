<?php

namespace Modules\FoodInspection\Support;

use Modules\FoodInspection\Data\Requests\Step2DetailData;

class Step2RowAttributes
{
    public static function from(Step2DetailData $d, int $lineNo): array
    {
        return [
            'menu_dish_id'      => $d->menu_dish_id,
            'line_no'           => $lineNo,
            'meal_time'         => $d->meal_time,
            'dish_name'         => $d->dish_name,
            'main_ingredients'  => $d->main_ingredients,
            'quantity'          => $d->quantity,
            'prep_time'         => $d->prep_time,
            'cook_time'         => $d->cook_time,
            'hygiene_personnel' => $d->hygiene_personnel,
            'hygiene_equipment' => $d->hygiene_equipment,
            'hygiene_area'      => $d->hygiene_area,
            'sensory_eval'      => $d->sensory_eval,
            'action_taken'      => $d->isFailed() ? $d->action_taken : null,
        ];
    }
}
