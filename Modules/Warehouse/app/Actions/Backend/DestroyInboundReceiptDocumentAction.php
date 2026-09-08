<?php

namespace Modules\Warehouse\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Models\InboundReceiptDocument;

class DestroyInboundReceiptDocumentAction
{
    use AsAction;

    public function handle(InboundReceiptDocument $document): void
    {
        $document->delete();
    }
}
