<?php

namespace Modules\SalesOrder\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintLogAttribute extends TenantAwareModel
{
    /**
     * Log in tem là bản ghi lịch sử bất biến: chỉ được tạo mới, không được sửa hay xóa
     * (in lại chỉ đọc lại đúng dữ liệu đã lưu).
     */
    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    protected $fillable = [
        'print_log_id',
        'attribute_key',
        'attribute_value',
    ];

    public function printLog(): BelongsTo
    {
        return $this->belongsTo(PrintLog::class, 'print_log_id');
    }
}
