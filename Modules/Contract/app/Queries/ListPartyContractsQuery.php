<?php

namespace Modules\Contract\Queries;

use App\Shared\Contracts\QueryInterface;

class ListPartyContractsQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $vendorId = null,
        public readonly ?string $customerId = null,
    ) {}
}
