<?php

namespace Modules\Warehouse\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\Warehouse\Models\TagRoll;

class GetTagRollAllocationMapQuery implements QueryInterface
{
    public function __construct(
        public readonly TagRoll $roll,
    ) {}
}
