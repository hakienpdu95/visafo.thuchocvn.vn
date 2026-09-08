<?php

namespace Modules\Warehouse\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\Warehouse\Models\Batch;

class GetBatchTagAllocationsQuery implements QueryInterface
{
    public function __construct(
        public readonly Batch $batch,
    ) {}
}
