<?php

namespace Modules\Warehouse\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Models\BatchDocument;

class DestroyBatchDocumentAction
{
    use AsAction;

    public function handle(BatchDocument $document): void
    {
        $document->delete();
    }
}
