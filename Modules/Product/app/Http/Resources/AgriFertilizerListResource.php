<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgriFertilizerListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'category'    => $this->category,
            'name'        => $this->name,
            'ingredients' => $this->ingredients,
            'applicant'   => $this->applicant,
            'is_banned'   => $this->is_banned,
            'status'      => $this->status,
        ];
    }
}
