<?php

namespace Modules\Warehouse\Models;

use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Product;
use Modules\Warehouse\Enums\RetailItemTagStatus;

class RetailItemTag extends Model
{
    use HasUlids;
    use BelongsToOrganization;

    protected $fillable = [
        'uid',
        'gs1_serial',
        'visual_sequence',
        'batch_id',
        'product_id',
        'serial_number',
        'qr_code',
        'status',
        'sold_at',
        'external_order_id',
        'outbound_order_id',
    ];

    protected function casts(): array
    {
        return [
            'serial_number'   => 'integer',
            'visual_sequence' => 'integer',
            'status'          => RetailItemTagStatus::class,
            'sold_at'         => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function externalOrder(): BelongsTo
    {
        return $this->belongsTo(\Modules\Sapo\Models\ExternalOrder::class);
    }

    public function outboundOrder(): BelongsTo
    {
        return $this->belongsTo(OutboundOrder::class);
    }
}
