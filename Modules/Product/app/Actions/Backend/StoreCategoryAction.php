<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Data\Requests\StoreCategoryData;
use Modules\Product\Models\Category;

class StoreCategoryAction
{
    use AsAction;

    public function handle(StoreCategoryData $data): Category
    {
        return Category::create([
            'code'        => $data->code,
            'name'        => $data->name,
            'description' => $data->description,
            'is_active'   => $data->is_active,
        ]);
    }
}
