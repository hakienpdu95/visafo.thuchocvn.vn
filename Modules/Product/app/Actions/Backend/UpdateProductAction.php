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
            'sku'          => $data->sku,
            'name'         => $data->name,
            'category_id'  => $data->category_id,
            'product_type' => $data->product_type->value,
            'unit'         => $data->unit,
            'shelf_life_days' => $data->shelf_life_days,
            'label_template_id' => $data->label_template_id,
            'status'       => $data->status->value,
        ]);

        return $product;
    }
}
