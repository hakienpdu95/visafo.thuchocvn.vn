<?php

namespace Modules\FoodInspection\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\FoodInspection\Models\FoodSampleLog;

class DestroyFoodSampleAction
{
    use AsAction;

    /** Phiếu đã có mẫu được hủy phải được giữ lại làm hồ sơ truy vết → không cho xóa. */
    public function handle(FoodSampleLog $log): string
    {
        if ($log->details()->whereNotNull('destroyed_at')->exists()) {
            throw ValidationException::withMessages(['log' => 'Không thể xóa phiếu đã có mẫu được hủy — cần giữ làm hồ sơ truy vết.']);
        }

        return DB::transaction(function () use ($log) {
            $label = $log->sample_date?->format('d/m/Y') . ' — ' . $log->customer_name;
            $log->details()->get()->each->delete();
            $log->delete();

            return $label;
        });
    }
}
