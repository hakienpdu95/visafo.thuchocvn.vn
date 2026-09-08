<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status   = $this->status;
        $category = $this->category_type;
        $cert     = $this->latestCompliance;

        return [
            'id'      => $this->id,
            'sku'     => $this->sku,
            'barcode' => $this->barcode,
            'name'    => $this->name,

            'brand_name' => $this->brand?->name,

            'category_type_value' => $category->value,
            'category_type_label' => $category->label(),

            'status_value' => $status->value,
            'status_label' => $status->label(),
            'status_badge' => $status->badgeClass(),

            'compliance_document_name' => $cert?->documentType?->name,
            'compliance_expired'       => (bool) $cert?->isExpired(),
            'compliance_expiring'      => (bool) $cert?->isExpiringWithinDays(30),

            'created_at' => $this->created_at?->format('d/m/Y'),

            'show_url'   => route('backend.products.show', $this->resource),
            'edit_url'   => route('backend.products.edit', $this->resource),
            'delete_url' => route('backend.products.destroy', $this->resource),
            'can_delete' => true,
        ];
    }
}
