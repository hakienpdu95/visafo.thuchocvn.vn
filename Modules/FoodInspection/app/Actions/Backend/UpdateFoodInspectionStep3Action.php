<?php

namespace Modules\FoodInspection\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\FoodInspection\Data\Requests\Step3DetailData;
use Modules\FoodInspection\Data\Requests\StoreFoodInspectionStep3Data;
use Modules\FoodInspection\Models\FoodInspectionStep3Log;
use Modules\FoodInspection\Support\Step3LogHeader;
use Modules\FoodInspection\Support\Step3RowAttributes;

class UpdateFoodInspectionStep3Action
{
    use AsAction;

    /** Dòng có id → cập nhật; không id → thêm; dòng cũ không còn → xóa. Luôn chạm updated_at làm vết "sửa lần cuối". */
    public function handle(FoodInspectionStep3Log $log, StoreFoodInspectionStep3Data $data): FoodInspectionStep3Log
    {
        return DB::transaction(function () use ($log, $data) {
            $existing = $log->details()->get()->keyBy('id');
            $incoming = collect($data->details)->values();

            if ($incoming->first(fn (Step3DetailData $d) => $d->id !== null && ! $existing->has($d->id))) {
                throw ValidationException::withMessages(['details' => 'Có dòng món ăn không thuộc sổ kiểm thực này.']);
            }

            $log->fill(Step3LogHeader::from($data))->save();
            $log->touch();

            foreach ($incoming as $i => $detail) {
                $attributes = Step3RowAttributes::from($detail, $i + 1);
                $detail->id !== null ? $existing->get($detail->id)->update($attributes) : $log->details()->create($attributes);
            }

            $keptIds = $incoming->pluck('id')->filter()->all();
            $existing->reject(fn ($d) => in_array($d->id, $keptIds, true))->each->delete();

            return $log->refresh();
        });
    }
}
