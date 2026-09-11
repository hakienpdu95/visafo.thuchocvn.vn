<?php

namespace Modules\Compliance\Actions\Backend;

use App\Services\Media\MediaUploadService;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Compliance\Data\Requests\StoreComplianceDocumentData;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Compliance\Models\ComplianceDocument;

class UpdateComplianceDocumentAction
{
    use AsAction;

    public function __construct(
        private readonly MediaUploadService $uploadService,
    ) {}

    public function handle(ComplianceDocument $document, StoreComplianceDocumentData $data): ComplianceDocument
    {
        $document->update([
            'document_master_type_id' => $data->document_master_type_id,
            'document_number'         => $data->document_number,
            'classification_grade'    => $data->classification_grade,
            'issue_date'              => $data->issue_date,
            'expiration_date'         => $data->expiration_date,
            'issued_by'               => $data->issued_by,
            'notes'                   => $data->notes,
            'status'                  => $this->resolveStatus($data->expiration_date)->value,
        ]);

        if ($data->file !== null) {
            $this->uploadService->bulkDelete($document, 'attachments_private');
            $this->uploadService->upload($data->file, $document, 'attachments_private');
        }

        if ($data->pif_file !== null) {
            $this->uploadService->bulkDelete($document, 'pif');
            $this->uploadService->upload($data->pif_file, $document, 'pif');
        }

        return $document->fresh();
    }

    private function resolveStatus(?string $expirationDate): ComplianceDocumentStatus
    {
        if ($expirationDate === null) {
            return ComplianceDocumentStatus::Active;
        }

        return Carbon::parse($expirationDate)->isPast()
            ? ComplianceDocumentStatus::Expired
            : ComplianceDocumentStatus::Active;
    }
}
