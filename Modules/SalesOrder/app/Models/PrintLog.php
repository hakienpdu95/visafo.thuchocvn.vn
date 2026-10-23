<?php

namespace Modules\SalesOrder\Models;

use App\Traits\HasCreator;
use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\SalesOrder\Enums\PrintLogStatus;

class PrintLog extends TenantAwareModel
{
    use HasCreator;

    /** Chỉ các cột này được phép đổi sau khi in (QC thu hồi / đánh dấu lỗi, theo dõi in lại, cập nhật nguồn cung / lô, sửa NSX/HSD/mã lô ở Nhật ký TXNG). */
    private const MUTABLE_COLUMNS = [
        'status', 'status_reason', 'status_changed_by', 'status_changed_at',
        'print_count', 'last_printed_at', 'last_print_session_id', 'updated_at',
        'vendor_id', 'partner_product_id', 'product_batch_id', 'supplier_name',
        'mfg_date', 'exp_date', 'batch_code',
    ];

    /**
     * Log in tem: chỉ được tạo mới, không được xóa; sau khi in chỉ được đổi trạng thái, bộ đếm in lại, nguồn cung / lô và NSX/HSD/mã lô —
     * khối lượng, mã truy xuất... không được sửa.
     */
    protected static function booted(): void
    {
        static::creating(function (PrintLog $log): void {
            // Mã truy xuất ngẫu nhiên (không tuần tự) dùng trong QR công khai.
            if (empty($log->trace_code)) {
                do {
                    $code = Str::lower(Str::random(10));
                } while (static::withTrashed()->where('trace_code', $code)->exists());

                $log->trace_code = $code;
            }

            $log->last_print_session_id ??= $log->print_session_id;
            $log->last_printed_at ??= now();
        });
        static::updating(fn (PrintLog $log): bool => array_diff(array_keys($log->getDirty()), self::MUTABLE_COLUMNS) === []);
        static::deleting(fn () => false);
    }

    protected $fillable = [
        'order_item_id',
        'label_template_id',
        'trace_code',
        'print_session_id',
        'print_count',
        'last_printed_at',
        'last_print_session_id',
        'status',
        'status_reason',
        'status_changed_by',
        'status_changed_at',
        'weight_per_label',
        'mfg_date',
        'exp_date',
        'supplier_name',
        'vendor_id',
        'partner_product_id',
        'product_batch_id',
        'batch_code',
        'printed_by',
    ];

    protected function casts(): array
    {
        return [
            'status'           => PrintLogStatus::class,
            'status_changed_at' => 'datetime',
            'last_printed_at'  => 'datetime',
            'print_count'      => 'integer',
            'weight_per_label' => 'decimal:3',
            'mfg_date'         => 'date',
            'exp_date'         => 'date',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class, 'order_item_id')->withTrashed();
    }

    /** Mẫu tem đã chọn khi in (null = theo sản phẩm / mặc định). */
    public function labelTemplate(): BelongsTo
    {
        return $this->belongsTo(\Modules\LabelTemplate\Models\LabelTemplate::class, 'label_template_id');
    }

    /** Alias của item() — dùng trong luồng render tem động. */
    public function orderItem(): BelongsTo
    {
        return $this->item();
    }

    /** Alias của extraAttributes() — template tem dùng $printLog->attributes. */
    public function attributes(): HasMany
    {
        return $this->extraAttributes();
    }

    /** Thông tin bổ sung in trên tem (EAV) — snapshot tại thời điểm in. */
    public function extraAttributes(): HasMany
    {
        return $this->hasMany(PrintLogAttribute::class, 'print_log_id')->orderBy('created_at')->orderBy('id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(\Modules\Vendor\Models\Vendor::class);
    }

    /** Hàng hóa NCC chọn khi in (null với tem in trước khi có cột này). */
    public function partnerProduct(): BelongsTo
    {
        return $this->belongsTo(\Modules\Product\Models\PartnerProduct::class);
    }

    public function productBatch(): BelongsTo
    {
        return $this->belongsTo(\Modules\GoodsReceipt\Models\ProductBatch::class);
    }

    public function statusChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }

    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }

    /**
     * Tem đang lưu hành của một dòng hàng. "In tem" luôn in lại đúng các mã này; chọn nguồn / lô khác thì cập nhật nguồn
     * của chính các tem đó (giữ nguyên trace_code).
     */
    public function scopeActiveForItem(Builder $query, string $orderItemId): Builder
    {
        return $query->where('order_item_id', $orderItemId)
            ->where('status', PrintLogStatus::Active->value)
            ->orderBy('created_at')->orderBy('id');
    }

    public static function sourceDiffers(iterable $logs, array $source): bool
    {
        if (! ($source['source_selected'] ?? false)) {
            return false;
        }

        foreach ($logs as $log) {
            if ($log->product_batch_id !== ($source['product_batch_id'] ?? null)
                || $log->vendor_id !== ($source['vendor_id'] ?? null)
                || (array_key_exists('partner_product_id', $source) && $log->partner_product_id !== $source['partner_product_id'])
                || ($log->vendor_id === null && trim((string) $log->supplier_name) !== trim((string) ($source['supplier_name'] ?? '')))) {
                return true;
            }
        }

        return false;
    }

    public static function updateSource(iterable $logs, array $source): void
    {
        foreach ($logs as $log) {
            $vendorId = $source['vendor_id'] ?? null;
            $log->update([
                'vendor_id'          => $vendorId,
                // Form không gửi hàng hóa NCC → giữ nguyên nếu vẫn cùng NCC, đổi NCC thì bỏ (hàng hóa cũ thuộc NCC cũ).
                'partner_product_id' => array_key_exists('partner_product_id', $source)
                    ? $source['partner_product_id']
                    : ($log->vendor_id === $vendorId ? $log->partner_product_id : null),
                'product_batch_id'   => $source['product_batch_id'] ?? null,
                'supplier_name'    => $source['supplier_name'] ?? null,
            ]);
        }
    }

    /** @return array<string, int> khối lượng/tem (3 số lẻ) => số tem */
    public static function splitOf(iterable $groups): array
    {
        $split = [];
        foreach ($groups as $group) {
            [$weight, $qty] = $group instanceof self
                ? [$group->weight_per_label, 1]
                : [$group['weight'] ?? $group['weight_per_label'], $group['qty'] ?? $group['label_count']];
            $key = number_format((float) $weight, 3, '.', '');
            $split[$key] = ($split[$key] ?? 0) + (int) $qty;
        }
        ksort($split);

        return $split;
    }

    public static function splitDiffers(iterable $logs, iterable $groups): bool
    {
        return self::splitOf($logs) !== self::splitOf($groups);
    }

    public static function appendedGroups(iterable $logs, iterable $groups): array
    {
        $current = self::splitOf($logs);
        $added = [];
        $target = self::splitOf($groups);
        foreach ($current as $weight => $qty) {
            if (($target[$weight] ?? 0) < $qty) {
                return [];
            }
        }
        foreach ($target as $weight => $qty) {
            $extra = $qty - ($current[$weight] ?? 0);
            if ($extra > 0) {
                $added[] = ['weight_per_label' => (float) $weight, 'label_count' => $extra];
            }
        }

        return $added;
    }

    /** Đổi cách chia tem: hủy toàn bộ tem đang lưu hành của dòng hàng để thay bằng bộ mã mới. */
    public static function revokeForResplit(iterable $logs, ?string $userId, int $newCount): void
    {
        foreach ($logs as $log) {
            $log->update([
                'status'            => PrintLogStatus::Revoked,
                'status_reason'     => "Đã thay bằng {$newCount} tem mới do đổi cách chia tem.",
                'status_changed_by' => $userId,
                'status_changed_at' => now(),
            ]);
        }
    }

    /** Ghi nhận một lần in lại — chỉ bộ đếm/phiên in; dữ liệu nguồn đã in là bất biến. */
    public function markReprinted(string $sessionId): void
    {
        $this->update([
            'print_count'           => $this->print_count + 1,
            'last_printed_at'       => now(),
            'last_print_session_id' => $sessionId,
        ]);
    }

    public function printEvents(): HasMany
    {
        return $this->hasMany(LabelPrintEvent::class, 'print_log_id');
    }

    public function permissionModule(): string
    {
        return 'trace_log';
    }
}
