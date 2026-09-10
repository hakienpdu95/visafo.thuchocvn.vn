<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status      = $this->status;
        $productType = $this->product_type;
        $cert        = $this->latestDocument;

        return [
            'id'      => $this->id,
            'sku'     => $this->sku,
            'name'    => $this->name,

            'category_id'   => $this->category_id,
            'category_name' => $this->category?->name,

            'product_type_value' => $productType->value,
            'product_type_label' => $productType->label(),

            'status_value' => $status->value,
            'status_label' => $status->label(),
            'status_badge' => $status->badgeClass(),

            'compliance_document_name' => $cert?->documentType?->name,
            'compliance_expired'       => (bool) $cert?->isExpired(),
            'compliance_expiring'      => (bool) $cert?->isExpiringWithinDays(30),

            'created_at' => $this->created_at?->format('d/m/Y'),

            'edit_url'   => route('backend.products.edit', $this->resource),
            'delete_url' => route('backend.products.destroy', $this->resource),
            'can_delete' => true,
        ];
    }
}
