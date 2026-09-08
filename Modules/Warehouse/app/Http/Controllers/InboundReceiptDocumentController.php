<?php

namespace Modules\Warehouse\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Warehouse\Actions\Backend\DestroyInboundReceiptDocumentAction;
use Modules\Warehouse\Actions\Backend\StoreInboundReceiptDocumentAction;
use Modules\Warehouse\Data\Requests\StoreInboundReceiptDocumentData;
use Modules\Warehouse\Models\InboundReceipt;
use Modules\Warehouse\Models\InboundReceiptDocument;

class InboundReceiptDocumentController extends Controller
{
    private const NULLABLE_FIELDS = ['file_url'];

    public function store(Request $request, InboundReceipt $inboundReceipt, StoreInboundReceiptDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $inboundReceipt);

        $input = $request->all();
        foreach (self::NULLABLE_FIELDS as $field) {
            if (($input[$field] ?? null) === '') {
                $input[$field] = null;
            }
        }

        $data = StoreInboundReceiptDocumentData::validateAndCreate($input);
        $action->handle($inboundReceipt, $data);

        return redirect()->route('backend.inbound-receipts.show', $inboundReceipt)
            ->with('success', 'Đã thêm chứng từ mới cho phiếu nhập kho.');
    }

    public function destroy(InboundReceipt $inboundReceipt, InboundReceiptDocument $document, DestroyInboundReceiptDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $inboundReceipt);
        abort_unless($document->inbound_receipt_id === $inboundReceipt->id, 404);

        $action->handle($document);

        return redirect()->route('backend.inbound-receipts.show', $inboundReceipt)
            ->with('success', 'Đã xóa chứng từ.');
    }
}
