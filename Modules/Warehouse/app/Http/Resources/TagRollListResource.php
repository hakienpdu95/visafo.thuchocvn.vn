<?php

namespace Modules\Warehouse\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagRollListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $counts = $this->liveCounts();

        return [
            'id'            => $this->id,
            'prefix'        => $this->prefix,
            'from_sequence' => $this->from_sequence,
            'to_sequence'   => $this->to_sequence,
            'count'         => $this->count,

            'provisioned' => $counts['provisioned'],
            'bound'       => $counts['bound'],

            'created_at'   => $this->created_at?->format('d/m/Y H:i'),
            'creator_name' => $this->creator?->name,

            'show_url' => route('backend.tag-rolls.show', $this->resource),
        ];
    }
}
