<?php

namespace Modules\Warehouse\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SapoSyncLogListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status;

        return [
            'id' => $this->id,

            'sold_at' => $this->sold_at?->format('d/m/Y H:i:s'),
            'serial'  => $this->gs1_serial ?? $this->serial_number,

            'product_name' => $this->product?->name,

            'batch_code' => $this->batch?->internal_batch_code,
            'batch_url'  => $this->batch ? route('backend.batches.show', $this->batch) : null,

            'external_order_code'   => $this->externalOrder?->external_order_code,
            'external_order_status' => $this->externalOrder?->status,

            'status_value' => $status->value,
            'status_label' => $status->label(),
            'status_badge' => $status->badgeClass(),
        ];
    }
}
