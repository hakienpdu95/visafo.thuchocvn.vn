<?php

namespace Modules\Warehouse\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Warehouse\Actions\Backend\StoreBatchAction;
use Modules\Warehouse\Data\Requests\StoreBatchData;
use Modules\Warehouse\Models\InboundReceipt;

class BatchInReceiptController extends Controller
{
    private const NULLABLE_FIELDS = ['mfg_batch_number', 'mfg_date'];

    public function store(Request $request, InboundReceipt $inboundReceipt, StoreBatchAction $action): RedirectResponse
    {
        $this->authorize('update', $inboundReceipt);

        $input = $request->all();
        foreach (self::NULLABLE_FIELDS as $field) {
            if (($input[$field] ?? null) === '') {
                $input[$field] = null;
            }
        }

        $data  = StoreBatchData::validateAndCreate($input);
        $batch = $action->handle($inboundReceipt, $data);

        return redirect()->route('backend.inbound-receipts.show', $inboundReceipt)
            ->with('success', 'Đã tạo lô "' . $batch->internal_batch_code . '".');
    }
}
