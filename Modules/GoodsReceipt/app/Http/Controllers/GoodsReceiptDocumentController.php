<?php

namespace Modules\GoodsReceipt\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Compliance\Actions\Backend\DestroyComplianceDocumentAction;
use Modules\Compliance\Actions\Backend\StoreComplianceDocumentAction;
use Modules\Compliance\Data\Requests\StoreComplianceDocumentData;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\GoodsReceipt\Models\GoodsReceipt;
use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\Product\Models\DocumentMasterType;

class GoodsReceiptDocumentController extends Controller
{
    public function store(Request $request, GoodsReceipt $goodsReceipt, StoreComplianceDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $goodsReceipt);

        $request->validate([
            'product_batch_id'        => ['nullable', 'string', Rule::exists('product_batches', 'id')->where('goods_receipt_id', $goodsReceipt->id)],
            'document_master_type_id' => ['required', Rule::in(DocumentMasterType::query()->applicableTo('goods_receipt')->pluck('id')->all())],
            'files'                   => ['required', 'array', 'min:1'],
        ], [
            'product_batch_id.exists'          => 'Lô hàng không thuộc phiếu nhập này.',
            'document_master_type_id.required' => 'Vui lòng chọn loại hồ sơ.',
            'document_master_type_id.in'       => 'Loại hồ sơ không áp dụng cho phiếu nhập.',
            'files.required'                   => 'Vui lòng chọn ít nhất 1 file.',
        ]);

        $documentable = $request->filled('product_batch_id')
            ? ProductBatch::query()->findOrFail($request->input('product_batch_id'))
            : $goodsReceipt;

        $action->handle($documentable, StoreComplianceDocumentData::validateAndCreate($request->all()));

        return redirect()->route('backend.goods-receipts.show', $goodsReceipt)->with('success', 'Đã thêm hồ sơ lô hàng.');
    }

    public function destroy(GoodsReceipt $goodsReceipt, ComplianceDocument $document, DestroyComplianceDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $goodsReceipt);

        $owned = ($document->documentable_type === $goodsReceipt->getMorphClass() && $document->documentable_id === $goodsReceipt->id)
            || ($document->documentable_type === (new ProductBatch())->getMorphClass()
                && $goodsReceipt->batches()->whereKey($document->documentable_id)->exists());
        abort_unless($owned, 404);

        $action->handle($document);

        return redirect()->route('backend.goods-receipts.show', $goodsReceipt)->with('success', 'Đã xóa hồ sơ.');
    }
}
