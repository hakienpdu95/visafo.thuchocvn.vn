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

    /** Chỉ các cột này được phép đổi sau khi in (QC thu hồi / đánh dấu lỗi, theo dõi in lại). */
    private const MUTABLE_COLUMNS = [
        'status', 'status_reason', 'status_changed_by', 'status_changed_at',
        'print_count', 'last_printed_at', 'last_print_session_id', 'updated_at',
    ];

    /**
     * Log in tem là bản ghi lịch sử bất biến: chỉ được tạo mới, không được xóa, và chỉ được đổi
     * trạng thái (status*) — mọi dữ liệu đã in (khối lượng, NSX/HSD, mã truy xuất...) không được sửa.
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
        static::updating(function (PrintLog $log): bool {
            $dirty = array_diff(array_keys($log->getDirty()), self::MUTABLE_COLUMNS);
            $fillingSource = array_filter($dirty, fn (string $col) => in_array($col, ['vendor_id', 'supplier_name'], true) && $log->getOriginal($col) === null);

            return array_diff($dirty, $fillingSource) === [];
        });
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
     * Tem đang lưu hành của một dòng hàng (= đơn + sản phẩm), cùng lô nhập kho nếu có.
     * Có rồi thì "In tem" phải in lại đúng các mã này thay vì sinh mã mới (Check & Reuse).
     * Không so batch_code: frontend tự sinh nó từ NSX/HSD (LOT-ddmmyy-ddmmyy) nên đổi theo ngày in.
     */
    public function scopeActiveForItem(Builder $query, string $orderItemId, ?string $productBatchId, ?string $vendorId = null): Builder
    {
        return $query->where('order_item_id', $orderItemId)
            ->where('status', PrintLogStatus::Active->value)
            ->when($productBatchId, fn (Builder $q) => $q->where('product_batch_id', $productBatchId), fn (Builder $q) => $q->whereNull('product_batch_id'))
            ->when($vendorId, fn (Builder $q) => $q->where(fn (Builder $v) => $v->where('vendor_id', $vendorId)
                ->orWhere(fn (Builder $n) => $n->whereNull('vendor_id')->whereNull('supplier_name'))))
            ->orderBy('created_at')->orderBy('id');
    }

    /** Ghi nhận một lần in lại (không đổi dữ liệu đã in). */
    public function markReprinted(string $sessionId, ?string $vendorId = null, ?string $supplierName = null): void
    {
        $this->update(array_filter([
            'print_count'           => $this->print_count + 1,
            'last_printed_at'       => now(),
            'last_print_session_id' => $sessionId,
            'vendor_id'             => $this->vendor_id === null && $this->supplier_name === null ? $vendorId : null,
            'supplier_name'         => $this->supplier_name === null ? $supplierName : null,
        ], fn ($v) => $v !== null));
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
