<?php

namespace Modules\Product\Services;

final class ComplianceRequirement
{
    /**
     * @param string[] $documentTypeCodes Thỏa mãn nếu có MỘT TRONG các mã document_master_types.code này
     */
    public function __construct(
        public readonly string $label,
        public readonly array $documentTypeCodes,
        public readonly ?string $legalBasis = null,
    ) {}
}
