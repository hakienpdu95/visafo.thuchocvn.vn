<?php

namespace Modules\Compliance\Actions\Backend;

use App\Models\Media;
use App\Services\Media\MediaUploadService;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Compliance\Models\ComplianceDocument;

class DestroyComplianceDocumentMediaAction
{
    use AsAction;

    public function __construct(
        private readonly MediaUploadService $uploadService,
    ) {}

    public function handle(ComplianceDocument $document, Media $media): void
    {
        abort_unless(
            $media->model_type === ComplianceDocument::class && $media->model_id === $document->id,
            404
        );

        $this->uploadService->delete($media);
    }
}
