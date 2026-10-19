<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FarmingSourceListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'vendor_id'              => $this->vendor_id,
            'vendor_name'            => $this->vendor?->name,
            'source_code'            => $this->source_code,
            'name'                   => $this->name,
            'area_hectare'           => $this->area_hectare,
            'area_value'             => $this->area_value,
            'area_unit'              => $this->area_unit?->value,
            'area_text'              => $this->areaText(),
            'water_source'           => $this->water_source,
            'address'                => $this->address,
            'status'                 => $this->status,
            'pre_season_checked_at'  => $this->pre_season_checked_at?->format('d/m/Y H:i'),
        ];
    }
}
