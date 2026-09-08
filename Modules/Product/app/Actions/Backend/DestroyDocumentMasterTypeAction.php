<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Models\DocumentMasterType;

class DestroyDocumentMasterTypeAction
{
    use AsAction;

    public function handle(DocumentMasterType $documentMasterType): void
    {
        $documentMasterType->delete();
    }
}
