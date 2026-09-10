<?php

namespace Modules\Product\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Traits\HasAutoCode;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\Product\Enums\PartnerProductStatus;
use Modules\Vendor\Models\Vendor;

class PartnerProduct extends TenantAwareModel
{
    use HasAutoCode;

    public function autoCodeColumn(): string
    {
        return 'vendor_sku';
    }

    public function autoCodeSequenceType(): string
    {
        return 'partner_product';
    }

    protected $fillable = [
        'vendor_id',
        'product_id',
        'vendor_sku',
        'name',
        'manufacturer_name',
        'origin_address',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => PartnerProductStatus::class,
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(ComplianceDocument::class, 'documentable');
    }

    public function activeDocuments(): MorphMany
    {
        return $this->documents()->where('status', ComplianceDocumentStatus::Active->value);
    }
}
