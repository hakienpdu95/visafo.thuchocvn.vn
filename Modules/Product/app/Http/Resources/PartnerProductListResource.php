<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartnerProductListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status;

        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'vendor_sku'  => $this->vendor_sku,
            'vendor_name' => $this->vendor?->name,
            'product_sku' => $this->product?->sku,
            'product_name' => $this->product?->name,

            'status_value' => $status->value,
            'status_label' => $status->label(),
            'status_badge' => $status->badgeClass(),

            'created_at' => $this->created_at?->format('d/m/Y'),

            'show_url'   => route('backend.partner-products.show', $this->resource),
            'edit_url'   => route('backend.partner-products.edit', $this->resource),
            'delete_url' => route('backend.partner-products.destroy', $this->resource),
            'can_delete' => true,
        ];
    }
}
