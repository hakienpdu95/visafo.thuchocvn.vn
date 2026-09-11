<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgriFertilizerMobileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'category'    => $this->category,
            'name'        => $this->name,
            'ingredients' => $this->ingredients,
        ];
    }
}
