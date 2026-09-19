<?php

namespace Modules\SalesOrder\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PrintLog extends TenantAwareModel
{
    /**
     * Log in tem là bản ghi lịch sử bất biến: chỉ được tạo mới, không được sửa hay xóa
     * (in lại chỉ đọc lại đúng dữ liệu đã lưu).
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
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    protected $fillable = [
        'order_item_id',
        'label_template_id',
        'trace_code',
        'weight_per_label',
        'label_count',
        'mfg_date',
        'exp_date',
        'supplier_name',
        'printed_by',
    ];

    protected function casts(): array
    {
        return [
            'weight_per_label' => 'decimal:3',
            'label_count'      => 'integer',
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

    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }
}
