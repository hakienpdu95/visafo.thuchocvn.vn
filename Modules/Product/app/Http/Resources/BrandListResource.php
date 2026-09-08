<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,

            'logo_thumb_url'  => $this->getFirstMediaUrl('logo', 'thumb'),
            'logo_medium_url' => $this->getFirstMediaUrl('logo', 'medium'),

            'products_count' => $this->products_count,

            'edit_url'    => route('backend.brands.edit', $this->resource),
            'delete_url'  => route('backend.brands.destroy', $this->resource),
            'can_delete'  => $this->products_count === 0,
        ];
    }
}
