<?php

namespace Modules\Customer\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Customer\Data\Requests\StoreCustomerDeliveryPointData;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerDeliveryPoint;

class StoreCustomerDeliveryPointAction
{
    use AsAction;

    public function handle(Customer $customer, StoreCustomerDeliveryPointData $data): CustomerDeliveryPoint
    {
        return $customer->deliveryPoints()->create([
            'site_name'      => $data->site_name,
            'address'        => $data->address,
            'province_code'  => $data->province_code,
            'ward_code'      => $data->ward_code,
            'receiver_name'  => $data->receiver_name,
            'receiver_phone' => $data->receiver_phone,
            'note'           => $data->note,
        ]);
    }
}
