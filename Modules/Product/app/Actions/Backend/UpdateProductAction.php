<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Data\Requests\UpdateProductData;
use Modules\Product\Models\Product;

class UpdateProductAction
{
    use AsAction;

    public function handle(Product $product, UpdateProductData $data): Product
    {
        $product->update([
            'sku'           => $data->sku,
            'barcode'       => $data->barcode,
            'name'          => $data->name,
            'brand_id'      => $data->brand_id,
            'category_type' => $data->category_type->value,
            'unit'          => $data->unit,
            'status'        => $data->status->value,
        ]);

        return $product;
    }
}
