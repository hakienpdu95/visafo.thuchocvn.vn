<?php

namespace Modules\Recall\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\Recall\Models\AdverseEventReport;

class GetAdverseEventReportQuery implements QueryInterface
{
    public function __construct(
        public readonly AdverseEventReport $report,
    ) {}
}
