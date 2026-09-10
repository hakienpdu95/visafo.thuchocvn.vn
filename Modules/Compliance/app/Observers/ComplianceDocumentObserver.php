<?php

namespace Modules\Compliance\Observers;

use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Compliance\Models\ComplianceDocument;

class ComplianceDocumentObserver
{
    public function creating(ComplianceDocument $document): void
    {
        $status = $document->status instanceof ComplianceDocumentStatus
            ? $document->status
            : ComplianceDocumentStatus::from((string) $document->status);

        if ($status !== ComplianceDocumentStatus::Active) {
            return;
        }

        ComplianceDocument::where('documentable_type', $document->documentable_type)
            ->where('documentable_id', $document->documentable_id)
            ->where('document_master_type_id', $document->document_master_type_id)
            ->where('status', ComplianceDocumentStatus::Active->value)
            ->update(['status' => ComplianceDocumentStatus::Superseded->value]);
    }
}
