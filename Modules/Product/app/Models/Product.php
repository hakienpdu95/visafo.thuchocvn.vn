<?php

namespace Modules\Product\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerProduct;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Enums\ProductType;

class Product extends TenantAwareModel
{
    protected $fillable = [
        'sku',
        'name',
        'category_id',
        'product_type',
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
            'product_type' => ProductType::class,
            'status'       => ProductStatus::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function partnerProducts(): HasMany
    {
        return $this->hasMany(PartnerProduct::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(ComplianceDocument::class, 'documentable');
    }

    public function activeDocuments(): MorphMany
    {
        return $this->documents()->where('status', ComplianceDocumentStatus::Active->value);
    }

    public function latestDocument(): MorphOne
    {
        return $this->morphOne(ComplianceDocument::class, 'documentable')->ofMany('issue_date', 'max');
    }

    public function hasValidDocument(DocumentMasterType $documentType): bool
    {
        return $this->activeDocuments()
            ->where('document_master_type_id', $documentType->id)
            ->where(fn ($q) => $q->whereNull('expiration_date')->orWhere('expiration_date', '>=', now()))
            ->exists();
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class)
            ->using(CustomerProduct::class)
            ->withPivot('status')
            ->withTimestamps();
    }
}
