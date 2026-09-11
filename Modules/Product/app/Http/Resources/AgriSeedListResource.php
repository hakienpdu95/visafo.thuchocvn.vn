<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgriSeedListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'crop_type'        => $this->crop_type,
            'name'             => $this->name,
            'author_applicant' => $this->author_applicant,
            'decision_number'  => $this->decision_number,
            'is_banned'        => $this->is_banned,
            'status'           => $this->status,

            'edit_url' => route('backend.master-data.seeds.edit', $this->resource),
        ];
    }
}
