<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgriPesticideListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'category'            => $this->category,
            'trade_name'          => $this->trade_name,
            'active_ingredients'  => $this->active_ingredients,
            'target_pest'         => $this->target_pest,
            'applicant'           => $this->applicant,
            'quarantine_days'     => $this->quarantine_days,
            'is_banned'           => $this->is_banned,
            'status'              => $this->status,

            'edit_url' => route('backend.master-data.pesticides.edit', $this->resource),
        ];
    }
}
