<?php

namespace Modules\GoodsReceipt\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\GoodsReceipt\Models\GoodsReceipt;
use Modules\GoodsReceipt\Models\ProductBatch;

class DeleteGoodsReceiptAction
{
    use AsAction;

    public function handle(GoodsReceipt $receipt): void
    {
        DB::transaction(function () use ($receipt) {
            $receipt = GoodsReceipt::query()->lockForUpdate()->findOrFail($receipt->id);

            if (! $receipt->isEditable()) {
                throw ValidationException::withMessages([
                    'receipt' => 'Lô của phiếu đã được in tem, kiểm duyệt truy xuất hoặc ghi QC — không thể xóa.',
                ]);
            }

            if ($receipt->documents()->exists()) {
                throw ValidationException::withMessages([
                    'receipt' => 'Phiếu đã có hồ sơ đính kèm — xóa hồ sơ ở trang chi tiết trước.',
                ]);
            }

            $batches = ProductBatch::withTrashed()->where('goods_receipt_id', $receipt->id)->get();

            UpdateGoodsReceiptAction::guardRemovable($batches->reject->trashed());

            $batches->each->forceDelete();
            $receipt->items()->withTrashed()->get()->each->forceDelete();
            $receipt->forceDelete();
        });
    }
}
