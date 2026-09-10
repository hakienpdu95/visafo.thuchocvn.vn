<?php

namespace App\Services;

use App\Models\SystemSequence;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Sinh mã ERP dạng PREFIX-XXXXXX từ bảng đếm độc lập `system_sequences`.
 *
 * Không bao giờ dùng max(id)/count() — bộ đếm nằm ở bảng riêng, chỉ tăng,
 * không bị ảnh hưởng khi bản ghi thực thể bị xóa mềm hoặc xóa cứng.
 */
class SequenceService
{
    public function generateCode(string $codeType): string
    {
        return DB::transaction(function () use ($codeType) {
            $sequence = SystemSequence::query()
                ->where('code_type', $codeType)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                throw new RuntimeException(
                    "Chưa khởi tạo bộ đếm mã cho loại \"{$codeType}\" trong bảng system_sequences.",
                );
            }

            $sequence->last_number++;
            $sequence->save();

            $number = str_pad((string) $sequence->last_number, $sequence->padding_length, '0', STR_PAD_LEFT);

            return $sequence->prefix . '-' . $number;
        });
    }
}
