<?php

namespace Modules\SalesOrder\Models;

use App\Traits\HasCreator;
use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Modules\Customer\Models\Customer;

class SalesOrder extends TenantAwareModel
{
    use HasCreator;

    public const MANUAL_REF_PREFIX = 'PX';

    private const LOCK_FLAGS = ['lock_qc', 'lock_print_logs', 'lock_print_events', 'lock_review'];

    private ?array $lockFlagCache = null;

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
        'customer_id',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class, 'order_id');
    }

    public function isManual(): bool
    {
        return $this->source_file_name === null;
    }

    public function isEditable(): bool
    {
        return $this->lockReason() === null;
    }

    public function lockReason(): ?string
    {
        return match (true) {
            $this->trashed() => 'Đơn đã bị xóa.',
            $this->status !== self::STATUS_PENDING || $this->shipped_at !== null => 'Đơn đã xuất kho.',
            default => null,
        };
    }

    public function deleteBlockReason(): ?string
    {
        return $this->lockReason() ?? ($this->lockFlag('lock_qc') ? 'Đơn đã có biên bản QC trước xuất.' : null);
    }

    public function hasPrintHistory(): bool
    {
        return $this->lockFlag('lock_print_logs') || $this->lockFlag('lock_print_events') || $this->lockFlag('lock_review');
    }

    private function lockFlag(string $key): bool
    {
        if (array_key_exists($key, $this->attributes)) {
            return (bool) $this->attributes[$key];
        }

        $this->lockFlagCache ??= static::query()->withoutGlobalScopes()->withLockFlags()->whereKey($this->getKey())->first()?->only(self::LOCK_FLAGS) ?? [];

        return (bool) ($this->lockFlagCache[$key] ?? false);
    }

    public function scopeWithLockFlags(Builder $query): Builder
    {
        $itemIds = fn () => DB::table('sales_order_items')->select('id')->whereColumn('sales_order_items.order_id', 'sales_orders.id');

        if ($query->getQuery()->columns === null) {
            $query->select('sales_orders.*');
        }

        return $query->addSelect([
            'lock_qc'           => DB::table('batch_quality_checks')->selectRaw('1')->whereIn('sales_order_item_id', $itemIds())->limit(1),
            'lock_print_logs'   => DB::table('print_logs')->selectRaw('1')->whereIn('order_item_id', $itemIds())->limit(1),
            'lock_print_events' => DB::table('label_print_events')->selectRaw('1')->whereIn('order_item_id', $itemIds())->limit(1),
            'lock_review'       => DB::table('trace_reviews')->selectRaw('1')->whereColumn('trace_reviews.sales_order_id', 'sales_orders.id')->limit(1),
        ]);
    }

    public function permissionModule(): string
    {
        return 'sales_order';
    }
}
