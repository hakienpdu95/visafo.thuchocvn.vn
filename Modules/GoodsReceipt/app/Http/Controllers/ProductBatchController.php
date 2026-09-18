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

        $data = UpdateProductBatchData::validateAndCreate($request->all());
        $action->handle($productBatch, $data);

        return redirect()->route('backend.goods-receipts.show', $productBatch->goods_receipt_id)
            ->with('success', 'Đã cập nhật NSX/HSD cho lô "' . $productBatch->batch_code . '".');
    }
}
