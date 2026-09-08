<?php

namespace Modules\Recall\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\Recall\Models\ProductRecall;

class GetRecallAffectedCustomersQuery implements QueryInterface
{
    public function __construct(
        public readonly ProductRecall $recall,
    ) {}
}
