<?php

namespace Modules\FoodInspection\Support;

use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerDeliveryPoint;
use Modules\FoodInspection\Data\Requests\StoreFoodSampleData;

class FoodSampleLogHeader
{
    public static function from(StoreFoodSampleData $data): array
    {
        $customer = Customer::query()->findOrFail($data->customer_id);
        $location = $data->location_name !== null ? trim($data->location_name) : null;

        $point = $location
            ? CustomerDeliveryPoint::query()->where('customer_id', $customer->id)->where('site_name', $location)->first()
            : null;

        return [
            'customer_id'       => $customer->id,
            'customer_name'     => $customer->name,
            'delivery_point_id' => $point?->id,
            'location_name'     => $location ?: null,
            'sample_date'       => $data->sample_date,
            'note'              => $data->note,
        ];
    }
}
