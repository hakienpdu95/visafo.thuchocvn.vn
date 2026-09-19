<?php

namespace Modules\TraceLog\Data\Requests;

use Illuminate\Validation\Rule;
use Modules\SalesOrder\Enums\PrintLogStatus;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class ChangeTraceLogStatusData extends Data
{
    public function __construct(
        public readonly PrintLogStatus $status,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $reason = null,

        /** Áp dụng cho mọi tem cùng lần in (print_session_id) — dùng khi thu hồi cả lô tem. */
        public readonly bool $apply_to_session = false,
    ) {}

    public static function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(PrintLogStatus::class)],
            // Thu hồi/lỗi bắt buộc nêu lý do (hiển thị cho người tiêu dùng trên trang truy xuất).
            'reason' => ['nullable', 'string', 'max:255', 'required_unless:status,' . PrintLogStatus::Active->value],
        ];
    }

    public static function messages(): array
    {
        return [
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.enum'     => 'Trạng thái không hợp lệ.',
            'reason.required_unless' => 'Vui lòng nhập lý do thu hồi/lỗi.',
            'reason.max'      => 'Lý do không được vượt quá :max ký tự.',
        ];
    }
}
