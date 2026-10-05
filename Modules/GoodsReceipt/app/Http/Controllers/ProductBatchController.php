<?php

namespace Modules\GoodsReceipt\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\GoodsReceipt\Actions\Backend\StoreBatchQualityCheckAction;
use Modules\GoodsReceipt\Actions\Backend\UpdateProductBatchAction;
use Modules\GoodsReceipt\Data\Requests\UpdateProductBatchData;
use Modules\GoodsReceipt\Enums\QualityCheckResult;
use Modules\GoodsReceipt\Enums\QualityCheckStage;
use Modules\GoodsReceipt\Models\ProductBatch;

class ProductBatchController extends Controller
{
    public function update(Request $request, ProductBatch $productBatch, UpdateProductBatchAction $action): RedirectResponse
    {
        $this->authorize('update', $productBatch);

        // NCC có lô canh tác cho mặt hàng này → bắt buộc chọn đúng lô nguồn (trang truy xuất đi theo khóa này).
        $candidateIds = ProductBatch::farmingBatchCandidates($productBatch->goodsReceipt?->vendor_id, [$productBatch->product_id])
            ->pluck('farming_batches.id')->all();
        $request->validate([
            'farming_batch_id' => [$candidateIds ? 'required' : 'nullable', Rule::in($candidateIds)],
        ], [
            'farming_batch_id.required' => 'Nhà cung cấp có nhật ký canh tác cho mặt hàng này — vui lòng chọn lô canh tác nguồn.',
            'farming_batch_id.in'       => 'Lô canh tác không thuộc nhà cung cấp / mặt hàng của lô nhập này.',
        ]);

        // Bỏ các dòng thông tin bổ sung trống hoàn toàn.
        $extra = collect($request->input('extra_attributes', []))
            ->filter(fn ($a) => is_array($a) && (trim((string) ($a['key'] ?? '')) !== '' || trim((string) ($a['value'] ?? '')) !== ''))
            ->map(fn ($a) => ['key' => trim((string) ($a['key'] ?? '')), 'value' => trim((string) ($a['value'] ?? ''))])
            ->values()->all();

        $data = UpdateProductBatchData::validateAndCreate(array_merge($request->all(), ['extra_attributes' => $extra]));
        $action->handle($productBatch, $data);

        return redirect()->route('backend.goods-receipts.show', $productBatch->goods_receipt_id)
            ->with('success', 'Đã cập nhật lô "' . $productBatch->batch_code . '".');
    }

    /** Ghi phiếu QC tiếp nhận / cảm quan cho lô nhập kho. */
    public function storeQualityCheck(Request $request, ProductBatch $productBatch, StoreBatchQualityCheckAction $action): RedirectResponse
    {
        $this->authorize('update', $productBatch);

        $data = $request->validate([
            'stage'      => ['required', Rule::in(array_map(fn ($s) => $s->value, QualityCheckStage::batchStages()))],
            'result'     => ['required', Rule::enum(QualityCheckResult::class)],
            'checked_at' => ['nullable', 'date', 'before_or_equal:now'],
            'note'       => ['nullable', 'string', 'max:500'],
        ], [
            'stage.required'             => 'Vui lòng chọn khâu kiểm tra.',
            'result.required'            => 'Vui lòng chọn kết quả.',
            'checked_at.before_or_equal' => 'Thời điểm kiểm tra không được ở tương lai.',
        ]);

        $check = $action->handle(array_merge($data, ['product_batch_id' => $productBatch->id]));

        return redirect()->route('backend.goods-receipts.show', $productBatch->goods_receipt_id)
            ->with('success', 'Đã ghi ' . mb_strtolower($check->stage->label()) . ' cho lô "' . $productBatch->batch_code . '": ' . $check->result->label() . '.');
    }
}
