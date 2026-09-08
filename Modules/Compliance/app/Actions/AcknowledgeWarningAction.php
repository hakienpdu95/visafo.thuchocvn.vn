<?php

namespace Modules\Compliance\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Compliance\Enums\WarningStatus;
use Modules\Compliance\Models\ComplianceWarning;

class AcknowledgeWarningAction
{
    use AsAction;

    public function handle(ComplianceWarning $warning): ComplianceWarning
    {
        $warning->update([
            'status'          => WarningStatus::Acknowledged->value,
            'acknowledged_by' => auth()->id(),
            'acknowledged_at' => now(),
        ]);

        return $warning;
    }
}
