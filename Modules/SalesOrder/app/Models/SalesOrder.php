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
        'delivery_date',
        'status',
        'source_file_name',
        'imported_by',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
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
