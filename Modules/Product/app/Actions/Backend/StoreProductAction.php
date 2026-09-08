<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Data\Requests\StoreProductData;
use Modules\Product\Models\Product;

class StoreProductAction
{
    use AsAction;

    public function handle(StoreProductData $data): Product
    {
        return Product::create([
            'sku'           => $data->sku,
            'barcode'       => $data->barcode,
            'name'          => $data->name,
            'brand_id'      => $data->brand_id,
            'category_type' => $data->category_type->value,
            'unit'          => $data->unit,
            'status'        => $data->status->value,
        ]);
    }
}
