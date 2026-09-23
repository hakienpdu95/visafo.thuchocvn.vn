<?php

namespace Modules\SalesOrder\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\SalesOrder\Models\SalesOrder;

class SalesOrderListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'misa_ref_id' => $this->misa_ref_id,
            'customer_name' => $this->customer_name,
            'delivery_address' => $this->delivery_address,
            'delivery_date' => $this->delivery_date?->format('d/m/Y'),
            'status' => $this->status,
            'status_label' => SalesOrder::statusLabels()[$this->status] ?? $this->status,
            'items_count' => $this->items_count,
            'source_file_name' => $this->source_file_name,
            'created_at' => $this->created_at?->format('d/m/Y H:i'),
            'show_url' => route('backend.sales-orders.show', $this->resource),
        ];
    }
}
