<?php

namespace Modules\Vendor\Models;

use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Vendor\Enums\VendorCertificateType;
use Spatie\MediaLibrary\HasMedia;

class VendorCertificate extends Model implements HasMedia
{
    use HasUlids;
    use HasTenantMedia;

    public $timestamps = true;

    protected $fillable = [
        'vendor_id',
        'certificate_type',
        'certificate_number',
        'issue_date',
        'expiry_date',
        'issued_by',
        'is_active',
        'renewal_deadline',
    ];

    protected function casts(): array
    {
        return [
            'certificate_type' => VendorCertificateType::class,
            'issue_date'       => 'date',
            'expiry_date'      => 'date',
            'renewal_deadline' => 'date',
            'is_active'        => 'boolean',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    public function isExpiringWithinDays(int $days): bool
    {
        return $this->expiry_date !== null
            && ! $this->isExpired()
            && $this->expiry_date->lte(now()->addDays($days));
    }
}
