<?php

namespace Modules\Compliance\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Compliance\Models\ComplianceDocument;

class DestroyComplianceDocumentAction
{
    use AsAction;

    public function handle(ComplianceDocument $document): void
    {
        $document->delete();
    }
}
