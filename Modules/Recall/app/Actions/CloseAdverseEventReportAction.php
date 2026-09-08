<?php

namespace Modules\Recall\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Recall\Enums\AdverseEventStatus;
use Modules\Recall\Models\AdverseEventReport;

class CloseAdverseEventReportAction
{
    use AsAction;

    public function handle(AdverseEventReport $report): AdverseEventReport
    {
        $report->update(['status' => AdverseEventStatus::Closed->value]);

        return $report;
    }
}
