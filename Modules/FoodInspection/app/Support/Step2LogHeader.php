<?php

namespace Modules\FoodInspection\Support;

use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerDeliveryPoint;
use Modules\FoodInspection\Data\Requests\StoreFoodInspectionStep2Data;

/** Dựng phần header của Sổ Bước 2: snapshot tên cơ sở và khớp địa điểm với điểm giao/bếp ăn của khách hàng. */
class Step2LogHeader
{
    public static function from(StoreFoodInspectionStep2Data $data): array
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
            'inspection_date'   => $data->inspection_date,
            'note'              => $data->note,
            'failed_items_count' => collect($data->details)->filter(fn ($d) => $d->isFailed())->count(),
        ];
    }
}
