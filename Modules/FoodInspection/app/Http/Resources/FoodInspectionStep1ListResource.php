<?php

namespace Modules\FoodInspection\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FoodInspectionStep1ListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'inspected_at'        => $this->inspected_at?->format('d/m/Y H:i'),
            'customer_name'       => $this->customer_name,
            'inspection_location' => $this->inspection_location,
            'details_count'       => (int) $this->details_count,
            'failed_items_count'  => (int) $this->failed_items_count,
            'inspector_name'      => $this->inspector?->name,
            'show_url'            => route('backend.food-inspections.show', $this->resource),
        ];
    }
}
