<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Data\Requests\UpdateBrandData;
use Modules\Product\Models\Brand;

class UpdateBrandAction
{
    use AsAction;

    public function handle(Brand $brand, UpdateBrandData $data): Brand
    {
        $brand->update([
            'name'        => $data->name,
            'description' => $data->description,
        ]);

        return $brand;
    }
}
