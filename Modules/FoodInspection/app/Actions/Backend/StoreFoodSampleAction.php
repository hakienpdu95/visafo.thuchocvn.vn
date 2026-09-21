<?php

namespace Modules\FoodInspection\Actions\Backend;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\FoodInspection\Data\Requests\StoreFoodSampleData;
use Modules\FoodInspection\Enums\SampleStatus;
use Modules\FoodInspection\Models\FoodSampleLog;
use Modules\FoodInspection\Support\FoodSampleLogHeader;
use Modules\FoodInspection\Support\FoodSampleRowAttributes;

class StoreFoodSampleAction
{
    use AsAction;

    /** Nhịp 1 — lưu mẫu: phiếu ở trạng thái "Lưu mẫu"; hủy mẫu chỉ được điền ở nhịp 2 (sau ≥ 24 giờ). */
    public function handle(StoreFoodSampleData $data, ?User $creator): FoodSampleLog
    {
        return DB::transaction(function () use ($data, $creator) {
            $log = FoodSampleLog::create(FoodSampleLogHeader::from($data) + [
                'status'       => SampleStatus::Stored,
                'created_by'   => $creator?->id,
                'creator_name' => $creator?->name ?? '—',
            ]);

            foreach (collect($data->details)->values() as $i => $detail) {
                $log->details()->create(FoodSampleRowAttributes::from($detail, $i + 1));
            }

            $log->update(['status' => self::statusFor($log)]);

            return $log;
        });
    }

    /** Phiếu chỉ "Đã hủy" khi mọi mẫu đã được hủy. */
    public static function statusFor(FoodSampleLog $log): SampleStatus
    {
        $details = $log->details()->get();

        return $details->isNotEmpty() && $details->every(fn ($d) => $d->isDestroyed()) ? SampleStatus::Destroyed : SampleStatus::Stored;
    }
}
