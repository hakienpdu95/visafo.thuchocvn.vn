<?php

namespace Modules\TraceLog\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\SalesOrder\Models\PrintLog;

class GetTraceLogDetailQuery implements QueryInterface
{
    public function __construct(
        public readonly PrintLog $printLog,
    ) {}
}
