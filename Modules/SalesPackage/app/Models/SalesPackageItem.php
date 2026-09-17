<?php

namespace Modules\SalesPackage\Models;

use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function fileUrl(): ?string
    {
        $url = $this->is_custom
            ? $this->getFirstMediaUrl('custom_document')
            : $this->document?->getFirstMediaUrl('attachments_private');

        return $url ?: null;
    }
}
