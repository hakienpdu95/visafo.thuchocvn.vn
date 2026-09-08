<?php

namespace Modules\Sapo\Models;

use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Product;

class SapoProductSyncLog extends Model
{
    use HasUlids;
    use BelongsToOrganization;

    public $timestamps = false;

    protected $fillable = [
        'source',
        'topic',
        'status',
        'sapo_product_id',
        'sapo_variant_id',
        'product_id',
        'product_name',
        'sku',
        'message',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $log): void {
            $log->created_at ??= now();
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
