<?php

namespace Modules\FoodInspection\Support;

use Modules\FoodInspection\Data\Requests\SampleDetailData;

class FoodSampleRowAttributes
{
    public static function from(SampleDetailData $d, int $lineNo): array
    {
        $destroyed = filled($d->destroyed_at);

        return [
            'step3_detail_id' => $d->step3_detail_id,
            'menu_dish_id'    => $d->menu_dish_id,
            'line_no'         => $lineNo,
            'meal_time'       => $d->meal_time,
            'dish_name'       => $d->dish_name,
            'portion_qty'     => $d->portion_qty,
            'sample_volume'   => str_replace(',', '.', trim($d->sample_volume)),
            'container_type'  => $d->container_type,
            'storage_temp'    => $d->storage_temp,
            'sampled_at'      => $d->sampled_at,
            'sampler_name'    => $d->sampler_name,
            'destroyed_at'    => $destroyed ? $d->destroyed_at : null,
            'destroyer_name'  => $destroyed ? $d->destroyer_name : null,
            'quality_note'    => $destroyed ? ($d->quality_note ?: 'Đạt') : null,
        ];
    }
}
