<?php

namespace Modules\Warehouse\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OutboundOrderListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status;

        return [
            'id'                   => $this->id,
            'order_number'         => $this->order_number,
            'dealer_name'          => $this->dealer_name,
            'ordered_at'           => $this->ordered_at?->format('d/m/Y'),
            'picked_batches_count' => $this->picked_batches_count,

            'status_value' => $status->value,
            'status_label' => $status->label(),
            'status_badge' => $status->badgeClass(),

            'show_url' => route('backend.outbound-orders.show', $this->resource),
            'edit_url' => route('backend.outbound-orders.edit', $this->resource),
        ];
    }
}
