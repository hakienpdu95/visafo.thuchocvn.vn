<?php

namespace Modules\Vendor\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status;
        $cert   = $this->latestDocument;

        return [
            'id'                   => $this->id,
            'vendor_code'          => $this->vendor_code,
            'name'                 => $this->name,
            'tax_code'             => $this->tax_code,
            'representative_name'  => $this->representative_name,
            'email'                => $this->email,
            'phone_number'         => $this->phone_number,

            'status_value' => $status->value,
            'status_label' => $status->label(),
            'status_badge' => $status->badgeClass(),

            'certificate_type'     => $cert?->documentType?->name,
            'certificate_expired'  => (bool) $cert?->isExpired(),
            'certificate_expiring' => (bool) $cert?->isExpiringWithinDays(30),

            'created_at' => $this->created_at?->format('d/m/Y'),

            'show_url'   => route('backend.vendors.show', $this->resource),
            'edit_url'   => route('backend.vendors.edit', $this->resource),
            'delete_url' => route('backend.vendors.destroy', $this->resource),
            'can_delete' => true,
        ];
    }
}
