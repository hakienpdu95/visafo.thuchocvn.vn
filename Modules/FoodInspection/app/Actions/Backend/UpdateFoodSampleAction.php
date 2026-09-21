<?php

namespace Modules\FoodInspection\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\FoodInspection\Data\Requests\SampleDetailData;
use Modules\FoodInspection\Data\Requests\StoreFoodSampleData;
use Modules\FoodInspection\Models\FoodSampleLog;
use Modules\FoodInspection\Support\FoodSampleLogHeader;
use Modules\FoodInspection\Support\FoodSampleRowAttributes;

class UpdateFoodSampleAction
{
    use AsAction;

    /**
     * Sửa phiếu (nhịp 1 bổ sung / nhịp 2 hủy mẫu). Mẫu ĐÃ hủy là bằng chứng pháp lý nên bất biến:
     * dữ liệu gửi lên cho mẫu đó bị bỏ qua và mẫu đó không bị xóa.
     */
    public function handle(FoodSampleLog $log, StoreFoodSampleData $data): FoodSampleLog
    {
        return DB::transaction(function () use ($log, $data) {
            $existing = $log->details()->get()->keyBy('id');
            $incoming = collect($data->details)->values();

            if ($incoming->first(fn (SampleDetailData $d) => $d->id !== null && ! $existing->has($d->id))) {
                throw ValidationException::withMessages(['details' => 'Có mẫu không thuộc phiếu này.']);
            }

            $log->fill(FoodSampleLogHeader::from($data))->save();

            foreach ($incoming as $i => $detail) {
                $attributes = FoodSampleRowAttributes::from($detail, $i + 1);

                if ($detail->id === null) {
                    $log->details()->create($attributes);
                    continue;
                }

                $row = $existing->get($detail->id);
                if (! $row->isDestroyed()) {
                    $row->update($attributes);
                }
            }

            $keptIds = $incoming->pluck('id')->filter()->all();
            $existing->reject(fn ($d) => $d->isDestroyed() || in_array($d->id, $keptIds, true))->each->delete();

            $log->update(['status' => StoreFoodSampleAction::statusFor($log)]);
            $log->touch();

            return $log->refresh();
        });
    }
}
