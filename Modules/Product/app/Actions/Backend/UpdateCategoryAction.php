<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Data\Requests\UpdateCategoryData;
use Modules\Product\Models\Category;

class UpdateCategoryAction
{
    use AsAction;

    public function handle(Category $category, UpdateCategoryData $data): Category
    {
        $category->update([
            'code'        => $data->code,
            'name'        => $data->name,
            'description' => $data->description,
            'is_active'   => $data->is_active,
        ]);

        return $category;
    }
}
