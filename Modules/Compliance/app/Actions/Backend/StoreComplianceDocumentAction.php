<?php

namespace Modules\Compliance\Actions\Backend;

use App\Services\Media\MediaUploadService;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Compliance\Data\Requests\StoreComplianceDocumentData;
use Modules\Compliance\Models\ComplianceDocument;

class StoreComplianceDocumentAction
{
    use AsAction;

    public function __construct(
        private readonly MediaUploadService $uploadService,
    ) {}

    public function handle(Model $documentable, StoreComplianceDocumentData $data): ComplianceDocument
    {
        /** @var ComplianceDocument $document */
        $document = $documentable->documents()->create([
            'document_master_type_id' => $data->document_master_type_id,
            'document_number'         => $data->document_number,
            'classification_grade'    => $data->classification_grade,
            'issue_date'              => $data->issue_date,
            'expiration_date'         => $data->expiration_date,
            'issued_by'               => $data->issued_by,
            'notes'                   => $data->notes,
            'status'                  => $data->status->value,
        ]);

        if ($data->file !== null) {
            $this->uploadService->upload($data->file, $document, 'attachments_private');
        }

        if ($data->pif_file !== null) {
            $this->uploadService->upload($data->pif_file, $document, 'pif');
        }

        return $document;
    }
}
