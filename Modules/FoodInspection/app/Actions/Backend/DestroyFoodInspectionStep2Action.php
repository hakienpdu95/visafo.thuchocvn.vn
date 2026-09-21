<?php

namespace Modules\FoodInspection\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\FoodInspection\Models\FoodInspectionStep2Log;

class DestroyFoodInspectionStep2Action
{
    use AsAction;

    public function handle(FoodInspectionStep2Log $log): string
    {
        return DB::transaction(function () use ($log) {
            $label = $log->inspection_date?->format('d/m/Y') . ' — ' . $log->customer_name;
            $log->details()->get()->each->delete();
            $log->delete();

            return $label;
        });
    }
}
