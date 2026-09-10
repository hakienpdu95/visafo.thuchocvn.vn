<?php

namespace Modules\Vendor\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\Province;
use App\Models\Ward;
use App\Traits\HasAutoCode;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\Product\Models\PartnerProduct;
use Modules\Vendor\Enums\VendorStatus;

class Vendor extends TenantAwareModel
{
    use HasAutoCode;

    public function autoCodeColumn(): string
    {
        return 'vendor_code';
    }

    public function autoCodeSequenceType(): string
    {
        return 'vendor';
    }

    protected $fillable = [
        'vendor_code',
        'name',
        'tax_code',
        'address',
        'province_code',
        'ward_code',
        'phone_number',
        'email',
        'representative_name',
        'representative_title',
        'representative_phone',
        'representative_email',
        'contact_person_name',
        'contact_person_title',
        'contact_person_phone',
        'contact_person_email',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => VendorStatus::class,
        ];
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code', 'province_code');
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class, 'ward_code', 'ward_code');
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

    public function hasValidDocument(int|string $documentMasterTypeId): bool
    {
        return $this->activeDocuments()
            ->where('document_master_type_id', $documentMasterTypeId)
            ->where(fn ($q) => $q->whereNull('expiration_date')->orWhere('expiration_date', '>=', now()))
            ->exists();
    }
}
