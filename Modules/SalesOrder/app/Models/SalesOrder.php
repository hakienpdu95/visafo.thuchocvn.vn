<?php

namespace Modules\SalesOrder\Models;

use App\Traits\HasCreator;
use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends TenantAwareModel
{
    use HasCreator;

    public const STATUS_PENDING = 'pending';
    public const STATUS_SHIPPING = 'shipping';
    public const STATUS_DELIVERED = 'delivered';

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING   => 'Chờ xử lý',
            self::STATUS_SHIPPING  => 'Đã xuất kho',
            self::STATUS_DELIVERED => 'Đã giao',
        ];
    }

    protected $fillable = [
        'misa_ref_id',
        'customer_name',
        'delivery_address',
        'delivery_date',
        'delivery_code',
        'shipped_at',
        'delivered_at',
        'status',
        'source_file_name',
        'imported_by',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'shipped_at'    => 'datetime',
            'delivered_at'  => 'datetime',
        ];
    }

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

    public function permissionModule(): string
    {
        return 'sales_order';
    }
}
