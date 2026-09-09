<?php

namespace Modules\Contract\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status;

        return [
            'id'               => $this->id,
            'contract_number'  => $this->contract_number,
            'name'             => $this->name,
            'vendor_name'      => $this->vendor?->name,
            'contract_type'    => $this->contractType?->name,
            'total_value'      => $this->total_value,
            'start_date'       => $this->start_date?->format('d/m/Y'),
            'end_date'         => $this->end_date?->format('d/m/Y'),
            'is_auto_renew'    => (bool) $this->is_auto_renew,

            'status_value' => $status->value,
            'status_label' => $status->label(),
            'status_badge' => $status->badgeClass(),

            'created_at' => $this->created_at?->format('d/m/Y'),

            'show_url'   => route('backend.contracts.show', $this->resource),
            'edit_url'   => route('backend.contracts.edit', $this->resource),
            'delete_url' => route('backend.contracts.destroy', $this->resource),
            'can_delete' => true,
        ];
    }
}
