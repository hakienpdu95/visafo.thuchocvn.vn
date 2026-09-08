<?php

namespace Modules\Warehouse\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Data\Requests\StoreBatchDocumentData;
use Modules\Warehouse\Models\Batch;
use Modules\Warehouse\Models\BatchDocument;

class StoreBatchDocumentAction
{
    use AsAction;

    public function handle(Batch $batch, StoreBatchDocumentData $data): BatchDocument
    {
        return $batch->documents()->create([
            'document_code'   => $data->document_code->value,
            'document_number' => $data->document_number,
            'file_url'        => $data->file_url,
        ]);
    }
}
