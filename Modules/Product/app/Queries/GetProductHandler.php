<?php

namespace Modules\Product\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Product\Models\Product;

class GetProductHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Product
    {
        /** @var GetProductQuery $query */
        $product = $query->product;

        $product->load([
            'category',
            'compliances' => fn ($q) => $q->with('documentType')->latest('issue_date'),
        ]);

        return $product;
    }
}
