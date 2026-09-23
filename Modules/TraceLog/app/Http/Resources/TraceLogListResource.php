<?php

namespace Modules\TraceLog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TraceLogListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $item = $this->orderItem;
        $order = $item?->salesOrder;

        return [
            'id'            => $this->id,
            'trace_code'    => $this->trace_code,
            'product_name'  => $item?->product?->name ?? $item?->product_name_raw,
            'order_ref'     => $order?->misa_ref_id,
            'order_url'     => $order ? route('backend.sales-orders.show', $order) : null,
            'customer_name' => $order?->customer_name,
            'weight'        => number_format((float) $this->weight_per_label, 3),
            'supplier_name' => $this->supplier_name,
            'vendor_linked' => $this->vendor_id !== null,
            'batch_code'    => $this->batch_code,
            'receipt_batch' => $this->productBatch?->batch_code,
            'printed_at'    => $this->created_at?->format('d/m/Y H:i'),
            'printed_by'    => $this->printedBy?->name,
            'status'        => $this->status->value,
            'status_label'  => $this->status->label(),
            'status_badge'  => $this->status->badgeClass(),
            'detail_url'    => route('backend.api.trace-logs.show', $this->resource),
        ];
    }
}
