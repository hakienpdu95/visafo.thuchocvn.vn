<?php

namespace Modules\Product\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\Product\Models\PartnerProduct;

class GetPartnerProductQuery implements QueryInterface
{
    public function __construct(
        public readonly PartnerProduct $partnerProduct,
    ) {}
}
