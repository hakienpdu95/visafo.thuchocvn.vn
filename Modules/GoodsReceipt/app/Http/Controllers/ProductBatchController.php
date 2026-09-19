<?php

namespace Modules\GoodsReceipt\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\GoodsReceipt\Actions\Backend\UpdateProductBatchAction;
use Modules\GoodsReceipt\Data\Requests\UpdateProductBatchData;
use Modules\GoodsReceipt\Models\ProductBatch;

class ProductBatchController extends Controller
{
    public function update(Request $request, ProductBatch $productBatch, UpdateProductBatchAction $action): RedirectResponse
    {
        $this->authorize('update', $productBatch);

        // Bỏ các dòng thông tin bổ sung trống hoàn toàn.
        $extra = collect($request->input('extra_attributes', []))
            ->filter(fn ($a) => is_array($a) && (trim((string) ($a['key'] ?? '')) !== '' || trim((string) ($a['value'] ?? '')) !== ''))
            ->map(fn ($a) => ['key' => trim((string) ($a['key'] ?? '')), 'value' => trim((string) ($a['value'] ?? ''))])
            ->values()->all();

        $data = UpdateProductBatchData::validateAndCreate(array_merge($request->all(), ['extra_attributes' => $extra]));
        $action->handle($productBatch, $data);

        return redirect()->route('backend.goods-receipts.show', $productBatch->goods_receipt_id)
            ->with('success', 'Đã cập nhật NSX/HSD cho lô "' . $productBatch->batch_code . '".');
    }
}
