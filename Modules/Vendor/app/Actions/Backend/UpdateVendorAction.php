<?php

namespace Modules\Vendor\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Vendor\Data\Requests\UpdateVendorData;
use Modules\Vendor\Models\Vendor;

class UpdateVendorAction
{
    use AsAction;

    public function handle(Vendor $vendor, UpdateVendorData $data): Vendor
    {
        $vendor->update([
            'vendor_code'          => $data->vendor_code,
            'name'                 => $data->name,
            'tax_code'             => $data->tax_code,
            'address'              => $data->address,
            'phone_number'         => $data->phone_number,
            'email'                => $data->email,
            'representative_name'  => $data->representative_name,
            'status'               => $data->status->value,
        ]);

        return $vendor;
    }
}
