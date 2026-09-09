<?php

namespace Modules\Product\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Product\Enums\ComplianceStatus;
use Modules\Product\Enums\PartnerProductStatus;
use Modules\Vendor\Models\Vendor;

class PartnerProduct extends TenantAwareModel
{
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

    public function compliances(): HasMany
    {
        return $this->hasMany(PartnerProductCompliance::class);
    }

    public function activeCompliances(): HasMany
    {
        return $this->compliances()->where('status', ComplianceStatus::Active->value);
    }
}
