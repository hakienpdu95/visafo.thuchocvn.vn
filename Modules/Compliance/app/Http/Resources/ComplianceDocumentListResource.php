<?php

namespace Modules\Compliance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplianceDocumentListResource extends JsonResource
{
    private const DOCUMENTABLE_LABELS = [
        'vendor'          => 'Nhà cung cấp',
        'product'         => 'Sản phẩm Visafo',
        'partner_product' => 'Hàng hóa NCC',
    ];

    private const DOCUMENTABLE_ROUTES = [
        'vendor'          => 'backend.vendors.show',
        'product'         => 'backend.products.show',
        'partner_product' => 'backend.partner-products.show',
    ];

    public function toArray(Request $request): array
    {
        $status = $this->status;
        $type   = $this->documentable_type;

        return [
            'id' => $this->id,

            'document_type_name' => $this->documentType?->name,
            'document_number'    => $this->document_number,

            'documentable_type'  => $type,
            'documentable_label' => self::DOCUMENTABLE_LABELS[$type] ?? $type,
            'documentable_name'  => $this->documentable?->name,
            'documentable_url'   => ($this->documentable && isset(self::DOCUMENTABLE_ROUTES[$type]))
                ? route(self::DOCUMENTABLE_ROUTES[$type], $this->documentable)
                : null,

            'issue_date'      => $this->issue_date?->format('d/m/Y'),
            'expiration_date' => $this->expiration_date?->format('d/m/Y'),
            'is_expired'      => (bool) $this->isExpired(),
            'is_expiring'     => (bool) $this->isExpiringWithinDays(30),

            'status_value' => $status->value,
            'status_label' => $status->label(),
            'status_badge' => $status->badgeClass(),

            'created_at' => $this->created_at?->format('d/m/Y'),
        ];
    }
}
