<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Data\Requests\UpdatePartnerProductData;
use Modules\Product\Models\PartnerProduct;

class UpdatePartnerProductAction
{
    use AsAction;

    public function handle(PartnerProduct $partnerProduct, UpdatePartnerProductData $data): PartnerProduct
    {
        $partnerProduct->update([
            'vendor_id'         => $data->vendor_id,
            'product_id'        => $data->product_id,
            'vendor_sku'        => $data->vendor_sku,
            'name'              => $data->name,
            'manufacturer_name' => $data->manufacturer_name,
            'origin_address'    => $data->origin_address,
            'status'            => $data->status->value,
        ]);

        return $partnerProduct;
    }
}
