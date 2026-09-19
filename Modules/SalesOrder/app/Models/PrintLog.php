<?php

namespace Modules\SalesOrder\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\SalesOrder\Enums\PrintLogStatus;

class PrintLog extends TenantAwareModel
{
    /** Chỉ các cột này được phép đổi sau khi in (QC thu hồi / đánh dấu lỗi). */
    private const MUTABLE_COLUMNS = ['status', 'status_reason', 'status_changed_by', 'status_changed_at', 'updated_at'];

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
        });
        static::updating(fn (PrintLog $log) => array_diff(array_keys($log->getDirty()), self::MUTABLE_COLUMNS) === []);
        static::deleting(fn () => false);
    }

    protected $fillable = [
        'order_item_id',
        'label_template_id',
        'trace_code',
        'print_session_id',
        'status',
        'status_reason',
        'status_changed_by',
        'status_changed_at',
        'weight_per_label',
        'mfg_date',
        'exp_date',
        'supplier_name',
        'printed_by',
    ];

    protected function casts(): array
    {
        return [
            'status'           => PrintLogStatus::class,
            'status_changed_at' => 'datetime',
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

    public function statusChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }

    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }
}
