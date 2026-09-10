<?php

namespace Modules\Customer\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Customer\Data\Requests\UpdateCustomerData;
use Modules\Customer\Models\Customer;

class UpdateCustomerAction
{
    use AsAction;

    public function handle(Customer $customer, UpdateCustomerData $data): Customer
    {
        $customer->update([
            'customer_code'         => $data->customer_code,
            'name'                  => $data->name,
            'customer_group'        => $data->customer_group->value,
            'meal_model'            => $data->meal_model->value,
            'tax_code'              => $data->tax_code,
            'address'               => $data->address,
            'province_code'         => $data->province_code,
            'ward_code'             => $data->ward_code,
            'phone_number'          => $data->phone_number,
            'email'                 => $data->email,
            'representative_name'   => $data->representative_name,
            'representative_title'  => $data->representative_title,
            'representative_phone'  => $data->representative_phone,
            'representative_email'  => $data->representative_email,
            'pic_id'                => $data->pic_id,
            'status'                => $data->status->value,
        ]);

        return $customer;
    }
}
