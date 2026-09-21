<?php

namespace Modules\FoodInspection\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\FoodInspection\Models\FoodInspectionStep3Log;

class DestroyFoodInspectionStep3Action
{
    use AsAction;

    public function handle(FoodInspectionStep3Log $log): string
    {
        return DB::transaction(function () use ($log) {
            $label = $log->inspection_date?->format('d/m/Y') . ' — ' . $log->customer_name;
            $log->details()->get()->each->delete();
            $log->delete();

            return $label;
        });
    }
}
