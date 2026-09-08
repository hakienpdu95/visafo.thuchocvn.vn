<?php

namespace Modules\Warehouse\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Data\Requests\StoreInboundReceiptDocumentData;
use Modules\Warehouse\Models\InboundReceipt;
use Modules\Warehouse\Models\InboundReceiptDocument;

class StoreInboundReceiptDocumentAction
{
    use AsAction;

    public function handle(InboundReceipt $inboundReceipt, StoreInboundReceiptDocumentData $data): InboundReceiptDocument
    {
        return $inboundReceipt->documents()->create([
            'document_code'   => $data->document_code->value,
            'document_number' => $data->document_number,
            'file_url'        => $data->file_url,
        ]);
    }
}
