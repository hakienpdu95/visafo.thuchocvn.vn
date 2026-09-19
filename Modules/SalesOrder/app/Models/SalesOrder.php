<?php

namespace Modules\SalesOrder\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends TenantAwareModel
{
    public const STATUS_PENDING = 'pending';

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Chờ xử lý',
        ];
    }

    protected $fillable = [
        'misa_ref_id',
        'customer_name',
        'delivery_address',
        'status',
        'source_file_name',
        'imported_by',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class, 'order_id');
    }
}
