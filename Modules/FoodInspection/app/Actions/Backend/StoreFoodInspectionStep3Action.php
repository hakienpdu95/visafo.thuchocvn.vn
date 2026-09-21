<?php

namespace Modules\FoodInspection\Actions\Backend;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\FoodInspection\Data\Requests\StoreFoodInspectionStep3Data;
use Modules\FoodInspection\Models\FoodInspectionStep3Log;
use Modules\FoodInspection\Support\Step3LogHeader;
use Modules\FoodInspection\Support\Step3RowAttributes;

class StoreFoodInspectionStep3Action
{
    use AsAction;

    public function handle(StoreFoodInspectionStep3Data $data, ?User $inspector): FoodInspectionStep3Log
    {
        return DB::transaction(function () use ($data, $inspector) {
            $log = FoodInspectionStep3Log::create(Step3LogHeader::from($data) + [
                'inspected_by'   => $inspector?->id,
                'inspector_name' => $inspector?->name ?? '—',
            ]);

            foreach (collect($data->details)->values() as $i => $detail) {
                $log->details()->create(Step3RowAttributes::from($detail, $i + 1));
            }

            return $log;
        });
    }
}
