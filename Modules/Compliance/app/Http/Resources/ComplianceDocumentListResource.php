<?php

namespace Modules\Compliance\Http\Resources;

use App\Services\Media\MediaUrlService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplianceDocumentListResource extends JsonResource
{
    private const DOCUMENTABLE_LABELS = [
        'vendor'             => 'Nhà cung cấp',
        'product'            => 'Sản phẩm Visafo',
        'partner_product'    => 'Hàng hóa NCC',
        'internal_facility'  => 'Cơ sở nội bộ',
    ];

    private const DOCUMENTABLE_ROUTES = [
        'vendor'          => 'backend.vendors.show',
        'product'         => 'backend.products.edit',
        'partner_product' => 'backend.partner-products.show',
    ];

    public function toArray(Request $request): array
    {
        $status = $this->status;
        $type   = $this->documentable_type;
        $isShared = $type === null;

        $media = $this->getMedia('attachments_private');
        $urlService = app(MediaUrlService::class);

        return [
            'id' => $this->id,

            'document_type_name' => $this->documentType?->name ?? $this->custom_name,
            'document_number'    => $this->document_number,

            'is_shared'             => $isShared,
            'custom_name'           => $this->custom_name,
            'custom_category_value' => $this->custom_category?->value,
            'notes'                 => $this->notes,
            'edit_url'              => $isShared ? route('backend.document-repository.edit', $this->resource) : null,
            'update_url'            => $isShared ? route('backend.document-repository.update', $this->resource) : null,
            'delete_url'            => $isShared ? route('backend.document-repository.destroy', $this->resource) : null,

            'media_count' => $media->count(),
            'media'       => $media->map(fn ($m) => [
                'id'       => $m->id,
                'name'     => $m->file_name,
                'size'     => $m->size,
                'is_image' => str_starts_with($m->mime_type, 'image/'),
                'url'      => $urlService->url($m),
            ])->values(),

            'documentable_type'  => $type,
            'documentable_label' => $type === null ? 'Nội bộ dùng chung' : (self::DOCUMENTABLE_LABELS[$type] ?? $type),
            'documentable_name'  => $type === null ? ($this->custom_category?->label() ?? '—') : $this->documentable?->name,
            'documentable_url'   => match (true) {
                $type === 'internal_facility' && $this->documentable_id !== null
                    => route('backend.internal-compliance.index') . '#facility-' . $this->documentable_id,
                $this->documentable && isset(self::DOCUMENTABLE_ROUTES[$type])
                    => route(self::DOCUMENTABLE_ROUTES[$type], $this->documentable),
                default => null,
            },

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
