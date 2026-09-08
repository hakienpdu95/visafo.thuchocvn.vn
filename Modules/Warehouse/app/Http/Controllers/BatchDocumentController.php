<?php

namespace Modules\Warehouse\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Warehouse\Actions\Backend\DestroyBatchDocumentAction;
use Modules\Warehouse\Actions\Backend\StoreBatchDocumentAction;
use Modules\Warehouse\Data\Requests\StoreBatchDocumentData;
use Modules\Warehouse\Models\Batch;
use Modules\Warehouse\Models\BatchDocument;

class BatchDocumentController extends Controller
{
    private const NULLABLE_FIELDS = ['file_url'];

    public function store(Request $request, Batch $batch, StoreBatchDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $batch);

        $input = $request->all();
        foreach (self::NULLABLE_FIELDS as $field) {
            if (($input[$field] ?? null) === '') {
                $input[$field] = null;
            }
        }

        $data = StoreBatchDocumentData::validateAndCreate($input);
        $action->handle($batch, $data);

        return redirect()->route('backend.batches.show', $batch)
            ->with('success', 'Đã thêm chứng từ mới cho lô.');
    }

    public function destroy(Batch $batch, BatchDocument $document, DestroyBatchDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $batch);
        abort_unless($document->batch_id === $batch->id, 404);

        $action->handle($document);

        return redirect()->route('backend.batches.show', $batch)
            ->with('success', 'Đã xóa chứng từ.');
    }
}
