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

    /** Chỉ các cột này được phép đổi sau khi in (QC thu hồi / đánh dấu lỗi, theo dõi in lại, cập nhật nguồn cung / lô). */
    private const MUTABLE_COLUMNS = [
        'status', 'status_reason', 'status_changed_by', 'status_changed_at',
        'print_count', 'last_printed_at', 'last_print_session_id', 'updated_at',
        'vendor_id', 'product_batch_id', 'supplier_name',
    ];

    /**
     * Log in tem: chỉ được tạo mới, không được xóa; sau khi in chỉ được đổi trạng thái, bộ đếm in lại và nguồn cung / lô —
     * dữ liệu in trên tem (khối lượng, NSX/HSD, mã truy xuất...) không được sửa.
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
        return $this->belongsTo(SalesOrderItem::class, 'order_item_id');
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
                || ($log->vendor_id === null && trim((string) $log->supplier_name) !== trim((string) ($source['supplier_name'] ?? '')))) {
                return true;
            }
        }

        return false;
    }

    public static function updateSource(iterable $logs, array $source): void
    {
        foreach ($logs as $log) {
            $log->update([
                'vendor_id'        => $source['vendor_id'] ?? null,
                'product_batch_id' => $source['product_batch_id'] ?? null,
                'supplier_name'    => $source['supplier_name'] ?? null,
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
