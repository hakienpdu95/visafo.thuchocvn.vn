<?php

namespace Modules\Product\Services;

use Modules\Product\Models\PartnerProductCompliance;

final class ComplianceRequirementResult
{
    public function __construct(
        public readonly ComplianceRequirement $requirement,
        public readonly bool $satisfied,
        public readonly ?PartnerProductCompliance $matchedCompliance = null,
    ) {}
}
