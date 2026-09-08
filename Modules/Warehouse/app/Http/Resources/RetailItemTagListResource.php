<?php

namespace Modules\Warehouse\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RetailItemTagListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status;

        return [
            'id'      => $this->id,
            'serial'  => $this->gs1_serial ?? $this->serial_number,
            'qr_code' => $this->qr_code,

            'status_value' => $status->value,
            'status_label' => $status->label(),
            'status_badge' => $status->badgeClass(),

            'sold_at' => $this->sold_at?->format('d/m/Y H:i'),

            'can_unbind_void' => in_array($status->value, ['bound', 'in_stock'], true),

            'unbind_url' => route('backend.tags.unbind', $this->resource),
            'void_url'   => route('backend.tags.void', $this->resource),
        ];
    }
}
