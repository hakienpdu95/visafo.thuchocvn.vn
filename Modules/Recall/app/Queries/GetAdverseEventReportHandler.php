<?php

namespace Modules\Recall\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Recall\Models\AdverseEventReport;

class GetAdverseEventReportHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): AdverseEventReport
    {
        /** @var GetAdverseEventReportQuery $query */
        $report = $query->report;

        $report->load(['product', 'batch']);

        return $report;
    }
}
