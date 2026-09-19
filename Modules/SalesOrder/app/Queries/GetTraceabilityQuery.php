<?php

namespace Modules\SalesOrder\Queries;

use App\Shared\Contracts\QueryInterface;

class GetTraceabilityQuery implements QueryInterface
{
    public function __construct(
        public readonly string $traceCode,
    ) {}
}
