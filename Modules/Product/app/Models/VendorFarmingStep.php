<?php

namespace Modules\Product\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Vendor\Models\Vendor;

class VendorFarmingStep extends TenantAwareModel
{
    protected $fillable = [
        'vendor_id',
        'partner_product_id',
        'step_name',
        'base_activity_type',
        'order_index',
    ];

    protected function casts(): array
    {
        return [
            'order_index' => 'integer',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function partnerProduct(): BelongsTo
    {
        return $this->belongsTo(PartnerProduct::class);
    }
}
