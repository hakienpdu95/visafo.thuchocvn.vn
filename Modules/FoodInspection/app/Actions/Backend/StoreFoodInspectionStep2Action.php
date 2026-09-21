<?php

namespace Modules\FoodInspection\Actions\Backend;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\FoodInspection\Data\Requests\StoreFoodInspectionStep2Data;
use Modules\FoodInspection\Models\FoodInspectionStep2Log;
use Modules\FoodInspection\Support\Step2LogHeader;
use Modules\FoodInspection\Support\Step2RowAttributes;

class StoreFoodInspectionStep2Action
{
    use AsAction;

    public function handle(StoreFoodInspectionStep2Data $data, ?User $inspector): FoodInspectionStep2Log
    {
        return DB::transaction(function () use ($data, $inspector) {
            $log = FoodInspectionStep2Log::create(Step2LogHeader::from($data) + [
                'inspected_by'   => $inspector?->id,
                'inspector_name' => $inspector?->name ?? '—',
            ]);

            foreach (collect($data->details)->values() as $i => $detail) {
                $log->details()->create(Step2RowAttributes::from($detail, $i + 1));
            }

            return $log;
        });
    }
}
