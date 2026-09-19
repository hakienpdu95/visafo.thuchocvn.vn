<?php

namespace Modules\GoodsReceipt\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\GoodsReceipt\Data\Requests\UpdateProductBatchData;
use Modules\GoodsReceipt\Models\ProductBatch;

class UpdateProductBatchAction
{
    use AsAction;

    public function handle(ProductBatch $productBatch, UpdateProductBatchData $data): ProductBatch
    {
        // HSD người dùng đã nhập luôn được giữ nguyên; chỉ tự tính khi để trống.
        $expDate = $data->exp_date ?: $productBatch->calculateExpDate($data->mfg_date)?->toDateString();

        $productBatch->update([
            'mfg_date' => $data->mfg_date,
            'exp_date' => $expDate,
        ]);

        // Thay toàn bộ thông tin bổ sung của lô bằng danh sách vừa gửi lên (xóa thật, không xóa mềm,
        // để không để lại dòng rác khi nhân viên bỏ bớt thông tin).
        DB::transaction(function () use ($productBatch, $data) {
            $productBatch->extraAttributes()->forceDelete();

            foreach ($data->extra_attributes ?? [] as $attr) {
                $productBatch->extraAttributes()->create([
                    'attribute_key'   => $attr['key'],
                    'attribute_value' => $attr['value'] ?: null,
                ]);
            }
        });

        return $productBatch;
    }
}
