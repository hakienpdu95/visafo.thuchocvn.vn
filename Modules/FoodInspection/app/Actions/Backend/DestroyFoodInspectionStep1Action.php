<?php

namespace Modules\FoodInspection\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\FoodInspection\Models\FoodInspectionStep1Log;

class DestroyFoodInspectionStep1Action
{
    use AsAction;

    /** Xóa mềm cả sổ và các dòng hàng (giữ lại cho truy xuất/Activity Log); trả về nhãn để hiện thông báo. */
    public function handle(FoodInspectionStep1Log $log): string
    {
        return DB::transaction(function () use ($log) {
            $label = $log->inspected_at?->format('d/m/Y H:i') ?? $log->id;
            $log->details()->get()->each->delete();
            $log->delete();

            return $label;
        });
    }
}
