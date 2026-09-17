<?php

namespace Modules\SalesPackage\Models;

use App\Services\Media\MediaUrlService;
use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\Product\Enums\DocumentGroupType;
use Spatie\MediaLibrary\HasMedia;

class SalesPackageItem extends Model implements HasMedia
{
    use HasUlids;
    use HasTenantMedia;

    protected $fillable = [
        'sales_package_id',
        'compliance_document_id',
        'document_group',
        'is_valid',
        'is_custom',
        'custom_name',
    ];

    protected function casts(): array
    {
        return [
            'document_group' => DocumentGroupType::class,
            'is_valid'       => 'boolean',
            'is_custom'      => 'boolean',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(SalesPackage::class, 'sales_package_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(ComplianceDocument::class, 'compliance_document_id');
    }

    public function displayName(): ?string
    {
        return $this->is_custom ? $this->custom_name : $this->document?->documentType?->name;
    }

    public function groupLabel(): string
    {
        return $this->document_group?->label() ?? 'Tài liệu bổ sung ngoài hệ thống';
    }

    /**
     * Toàn bộ file đính kèm của hạng mục này — tài liệu bổ sung có thể có 1-n file
     * (collection 'custom_document'), tài liệu hệ thống lấy từ ComplianceDocument
     * (collection 'attachments_private', cũng đã hỗ trợ 1-n file).
     *
     * @return array<int, array{id: string, name: string, size: int, is_image: bool, url: string}>
     */
    public function attachedFiles(): array
    {
        $media = $this->is_custom
            ? $this->getMedia('custom_document')
            : ($this->document?->getMedia('attachments_private') ?? new Collection());

        $urlService = app(MediaUrlService::class);

        return $media->map(fn ($m) => [
            'id'       => $m->id,
            'name'     => $m->file_name,
            'size'     => $m->size,
            'is_image' => str_starts_with($m->mime_type, 'image/'),
            'url'      => $urlService->url($m),
        ])->values()->all();
    }
}
