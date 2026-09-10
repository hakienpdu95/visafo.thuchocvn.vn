<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Models\Category;

class DestroyCategoryAction
{
    use AsAction;

    public function handle(Category $category): void
    {
        $category->delete();
    }
}
