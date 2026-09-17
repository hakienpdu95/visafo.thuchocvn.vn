<?php

namespace Modules\Compliance\Actions\Backend;

use App\Services\Media\MediaUploadService;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Compliance\Data\Requests\StoreSharedComplianceDocumentData;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Compliance\Models\ComplianceDocument;

class UploadSharedComplianceDocumentAction
{
    use AsAction;

    public function __construct(
        private readonly MediaUploadService $uploadService,
    ) {}

    public function handle(StoreSharedComplianceDocumentData $data): ComplianceDocument
    {
        $document = ComplianceDocument::create([
            'custom_name'     => $data->custom_name,
            'custom_category' => $data->custom_category->value,
            'notes'           => $data->notes,
            'status'          => ComplianceDocumentStatus::Active->value,
        ]);

        $this->uploadService->upload($data->file, $document, 'attachments_private');

        return $document;
    }
}
