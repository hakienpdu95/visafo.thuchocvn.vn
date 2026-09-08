<?php

namespace Modules\Vendor\Models;

use App\Foundation\Models\TenantAwareModel;
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
        'phone_number',
        'email',
        'representative_name',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => VendorStatus::class,
        ];
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
