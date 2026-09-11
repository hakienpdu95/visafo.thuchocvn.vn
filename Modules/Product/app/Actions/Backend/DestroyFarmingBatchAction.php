<?php

namespace Modules\Product\Actions\Backend;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Models\FarmingBatch;

class DestroyFarmingBatchAction
{
    use AsAction;

    public function handle(FarmingBatch $farmingBatch): string
    {
        if ($farmingBatch->logs()->exists()) {
            throw ValidationException::withMessages([
                'batch' => 'Không thể xóa lô đã có nhật ký canh tác — vui lòng liên hệ QC để xử lý.',
            ]);
        }

        $code = $farmingBatch->batch_code;
        $farmingBatch->delete();

        return $code;
    }
}
