<?php

namespace Modules\Product\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Product\Enums\ComplianceStatus;
use Modules\Product\Enums\ProductCategoryType;
use Modules\Product\Enums\ProductStatus;

class Product extends TenantAwareModel
{
    protected $fillable = [
        'sku',
        'barcode',
        'name',
        'brand_id',
        'category_type',
        'unit',
        'external_product_id',
        'sapo_product_id',
        'sapo_variant_id',
        'image_url',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'category_type' => ProductCategoryType::class,
            'status'        => ProductStatus::class,
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function compliances(): HasMany
    {
        return $this->hasMany(ProductCompliance::class);
    }

    public function activeCompliances(): HasMany
    {
        return $this->compliances()->where('status', ComplianceStatus::Active->value);
    }

    public function latestCompliance(): HasOne
    {
        return $this->hasOne(ProductCompliance::class)->ofMany('issue_date', 'max');
    }

    public function hasValidCompliance(DocumentMasterType $documentType): bool
    {
        return $this->activeCompliances()
            ->where('document_type_id', $documentType->id)
            ->where(fn ($q) => $q->whereNull('expiration_date')->orWhere('expiration_date', '>=', now()))
            ->exists();
    }
}
