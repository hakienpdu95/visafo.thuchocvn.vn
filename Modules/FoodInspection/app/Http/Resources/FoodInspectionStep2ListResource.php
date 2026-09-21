<?php

namespace Modules\FoodInspection\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FoodInspectionStep2ListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'inspection_date'    => $this->inspection_date?->format('d/m/Y'),
            'customer_name'      => $this->customer_name,
            'location_name'      => $this->location_name,
            'details_count'      => (int) $this->details_count,
            'failed_items_count' => (int) $this->failed_items_count,
            'inspector_name'     => $this->inspector_name,
            'show_url'           => route('backend.food-inspection-step2.show', $this->resource),
        ];
    }
}
