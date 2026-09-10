<?php

namespace Modules\Vendor\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Vendor\Data\Requests\StoreVendorData;
use Modules\Vendor\Models\Vendor;

class StoreVendorAction
{
    use AsAction;

    public function handle(StoreVendorData $data): Vendor
    {
        return Vendor::create([
            'name'                  => $data->name,
            'tax_code'              => $data->tax_code,
            'address'               => $data->address,
            'province_code'         => $data->province_code,
            'ward_code'             => $data->ward_code,
            'phone_number'          => $data->phone_number,
            'email'                 => $data->email,
            'representative_name'   => $data->representative_name,
            'representative_title' => $data->representative_title,
            'representative_phone' => $data->representative_phone,
            'representative_email' => $data->representative_email,
            'contact_person_name'  => $data->contact_person_name,
            'contact_person_title' => $data->contact_person_title,
            'contact_person_phone' => $data->contact_person_phone,
            'contact_person_email' => $data->contact_person_email,
            'status'               => $data->status->value,
        ]);
    }
}
