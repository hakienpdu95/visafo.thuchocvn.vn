<?php

namespace Modules\Compliance\Actions\Backend;

use App\Services\Media\MediaUploadService;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Compliance\Data\Requests\UpdateSharedComplianceDocumentData;
use Modules\Compliance\Models\ComplianceDocument;

class UpdateSharedComplianceDocumentAction
{
    use AsAction;

    public function __construct(
        private readonly MediaUploadService $uploadService,
    ) {}

    public function handle(ComplianceDocument $document, UpdateSharedComplianceDocumentData $data): ComplianceDocument
    {
        return DB::transaction(function () use ($document, $data) {
            $document->update([
                'custom_name'     => $data->custom_name,
                'custom_category' => $data->custom_category->value,
                'notes'           => $data->notes,
            ]);

            // Tệp mới luôn được gộp thêm vào — không xóa/thay thế tệp đã có.
            foreach ($data->files as $file) {
                $this->uploadService->upload($file, $document, 'attachments_private');
            }

            return $document->fresh();
        });
    }
}
