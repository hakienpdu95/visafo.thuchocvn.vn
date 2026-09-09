<?php

namespace Modules\Contract\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\Contract\Models\Contract;

class GetContractQuery implements QueryInterface
{
    public function __construct(
        public readonly Contract $contract,
    ) {}
}
