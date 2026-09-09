<?php

namespace Modules\Vendor\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\Province;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Vendor\Enums\VendorStatus;

class Vendor extends TenantAwareModel
{
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

    public function certificates(): HasMany
    {
        return $this->hasMany(VendorCertificate::class);
    }

    public function activeCertificates(): HasMany
    {
        return $this->certificates()->where('is_active', true);
    }

    public function latestCertificate(): HasOne
    {
        return $this->hasOne(VendorCertificate::class)->ofMany('issue_date', 'max');
    }

    public function hasValidCertificate(\Modules\Vendor\Enums\VendorCertificateType $type): bool
    {
        return $this->activeCertificates()
            ->where('certificate_type', $type->value)
            ->where(fn ($q) => $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now()))
            ->exists();
    }
}
