<?php

namespace Modules\Product\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Product\Models\PartnerProduct;

class GetPartnerProductHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): PartnerProduct
    {
        /** @var GetPartnerProductQuery $query */
        $partnerProduct = $query->partnerProduct;

        $partnerProduct->load(['vendor', 'product.category', 'documents.documentType']);

        return $partnerProduct;
    }
}
