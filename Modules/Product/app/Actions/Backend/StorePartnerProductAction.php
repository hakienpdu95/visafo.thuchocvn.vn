<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Data\Requests\StorePartnerProductData;
use Modules\Product\Models\PartnerProduct;

class StorePartnerProductAction
{
    use AsAction;

    public function handle(StorePartnerProductData $data): PartnerProduct
    {
        return PartnerProduct::create([
            'vendor_id'         => $data->vendor_id,
            'product_id'        => $data->product_id,
            'name'              => $data->name,
            'manufacturer_name' => $data->manufacturer_name,
            'origin_address'    => $data->origin_address,
            'status'            => $data->status->value,
        ]);
    }
}
