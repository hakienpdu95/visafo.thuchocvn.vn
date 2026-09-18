<?php

namespace Modules\GoodsReceipt\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoodsReceiptListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'misa_ref_id' => $this->misa_ref_id,
            'supplier_name' => $this->supplier_name,
            'vendor_name' => $this->vendor?->name,
            'receipt_date' => $this->receipt_date?->format('d/m/Y'),
            'items_count' => $this->items_count,
            'source_file_name' => $this->source_file_name,
            'created_at' => $this->created_at?->format('d/m/Y H:i'),
            'show_url' => route('backend.goods-receipts.show', $this->resource),
        ];
    }
}
