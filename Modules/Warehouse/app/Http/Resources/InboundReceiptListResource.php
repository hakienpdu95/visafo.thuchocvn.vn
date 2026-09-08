<?php

namespace Modules\Warehouse\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InboundReceiptListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status;

        return [
            'id'             => $this->id,
            'receipt_number' => $this->receipt_number,
            'vendor_name'    => $this->vendor?->name,
            'received_date'  => $this->received_date?->format('d/m/Y'),
            'batches_count'  => $this->batches_count,

            'status_value' => $status->value,
            'status_label' => $status->label(),
            'status_badge' => $status->badgeClass(),

            'show_url' => route('backend.inbound-receipts.show', $this->resource),
            'edit_url' => route('backend.inbound-receipts.edit', $this->resource),
        ];
    }
}
