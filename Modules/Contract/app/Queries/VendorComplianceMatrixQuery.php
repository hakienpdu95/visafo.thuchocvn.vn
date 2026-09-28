<?php

namespace Modules\Contract\Queries;

use App\Shared\Contracts\QueryInterface;

class VendorComplianceMatrixQuery implements QueryInterface
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 25,
        public readonly string $sortField = 'missing',
        public readonly string $sortDir = 'desc',
        public readonly ?string $search = null,
        public readonly ?string $sourceGroup = null,
        public readonly ?string $vendorStatus = 'active',
        public readonly ?string $state = null,
        public readonly int $warningDays = 30,
    ) {}
}
