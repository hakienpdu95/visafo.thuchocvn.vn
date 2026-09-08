<?php

namespace Modules\Product\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\Product\Models\Product;

class GetProductQuery implements QueryInterface
{
    public function __construct(
        public readonly Product $product,
    ) {}
}
