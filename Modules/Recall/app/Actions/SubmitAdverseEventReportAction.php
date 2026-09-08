<?php

namespace Modules\Recall\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Recall\Enums\AdverseEventStatus;
use Modules\Recall\Models\AdverseEventReport;

class SubmitAdverseEventReportAction
{
    use AsAction;

    public function handle(AdverseEventReport $report): AdverseEventReport
    {
        $report->update([
            'status'                    => AdverseEventStatus::Submitted->value,
            'submitted_to_authority_at' => now(),
        ]);

        return $report;
    }
}
