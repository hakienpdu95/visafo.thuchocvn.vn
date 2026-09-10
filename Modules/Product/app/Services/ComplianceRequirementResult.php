<?php

namespace Modules\Product\Services;

use Modules\Compliance\Models\ComplianceDocument;

final class ComplianceRequirementResult
{
    public function __construct(
        public readonly ComplianceRequirement $requirement,
        public readonly bool $satisfied,
        public readonly ?ComplianceDocument $matchedCompliance = null,
    ) {}
}
