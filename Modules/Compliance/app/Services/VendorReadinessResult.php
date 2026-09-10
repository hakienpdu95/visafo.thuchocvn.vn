<?php

namespace Modules\Compliance\Services;

use Modules\Compliance\Enums\ReadinessStatus;
use Modules\Vendor\Models\Vendor;

final class VendorReadinessResult
{
    /**
     * @param array<int, array{partnerProduct: \Modules\Product\Models\PartnerProduct, missing: \Modules\Product\Services\ComplianceRequirementResult[]}> $missingByProduct
     */
    public function __construct(
        public readonly Vendor $vendor,
        public readonly ReadinessStatus $status,
        public readonly array $missingByProduct,
        public readonly int $partnerProductCount,
    ) {}
}
