<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Models\Product;

class DestroyProductAction
{
    use AsAction;

    public function handle(Product $product): string
    {
        $name = $product->name;
        $product->delete();

        return $name;
    }
}
