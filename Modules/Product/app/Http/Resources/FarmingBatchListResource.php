<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FarmingBatchListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'batch_code'             => $this->batch_code,
            'vendor_name'            => $this->vendor?->name,
            'farming_source_name'    => $this->farmingSource?->name,
            'seed_name'              => $this->agriSeed?->name,
            'partner_product_name'   => $this->partnerProduct?->name,
            'sowing_date'            => $this->sowing_date?->format('d/m/Y'),
            'expected_harvest_date'  => $this->expected_harvest_date?->format('d/m/Y'),
            'status'                 => $this->status,
            'pre_harvest_status'     => $this->pre_harvest_status,

            'show_url' => route('backend.farming-batches.show', $this->resource),
        ];
    }
}
