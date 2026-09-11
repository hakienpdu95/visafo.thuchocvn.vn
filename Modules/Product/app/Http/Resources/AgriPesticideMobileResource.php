<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgriPesticideMobileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'category'           => $this->category,
            'trade_name'         => $this->trade_name,
            'active_ingredients' => $this->active_ingredients,
            'target_pest'        => $this->target_pest,
            'quarantine_days'    => $this->quarantine_days,
        ];
    }
}
