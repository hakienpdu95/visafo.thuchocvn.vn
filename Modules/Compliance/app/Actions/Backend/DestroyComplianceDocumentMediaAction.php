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
        // ComplianceDocument has a morph map alias ('compliance_document') registered in
        // ComplianceServiceProvider — Media::model_type stores that alias, not the FQCN.
        // getMorphClass() resolves to whichever form is actually in effect.
        abort_unless(
            $media->model_type === $document->getMorphClass() && $media->model_id === $document->id,
            404
        );

        $this->uploadService->delete($media);
    }
}
