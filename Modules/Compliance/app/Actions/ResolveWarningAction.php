<?php

namespace Modules\Compliance\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Compliance\Enums\WarningStatus;
use Modules\Compliance\Models\ComplianceWarning;

class ResolveWarningAction
{
    use AsAction;

    public function handle(ComplianceWarning $warning): ComplianceWarning
    {
        $warning->update([
            'status'      => WarningStatus::Resolved->value,
            'resolved_at' => now(),
        ]);

        return $warning;
    }
}
