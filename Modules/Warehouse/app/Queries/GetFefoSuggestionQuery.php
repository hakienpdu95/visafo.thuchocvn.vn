<?php

namespace Modules\Warehouse\Queries;

use App\Shared\Contracts\QueryInterface;

class GetFefoSuggestionQuery implements QueryInterface
{
    public function __construct(
        public readonly string $productId,
        public readonly int    $requestedQty = 0,
    ) {}
}
