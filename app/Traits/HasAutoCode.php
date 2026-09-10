<?php

namespace App\Traits;

use App\Services\SequenceService;

/**
 * Tự động sinh mã ERP (VD: NCC-000001) khi tạo bản ghi mới, nếu cột mã bị bỏ trống.
 *
 * Model dùng trait này phải khai báo:
 *   - autoCodeColumn(): tên cột lưu mã, VD: 'vendor_code'
 *   - autoCodeSequenceType(): loại bộ đếm tương ứng trong system_sequences, VD: 'vendor'
 */
trait HasAutoCode
{
    protected static function bootHasAutoCode(): void
    {
        static::creating(function ($model): void {
            $column = $model->autoCodeColumn();

            if (empty($model->{$column})) {
                $model->{$column} = app(SequenceService::class)->generateCode($model->autoCodeSequenceType());
            }
        });
    }
}
