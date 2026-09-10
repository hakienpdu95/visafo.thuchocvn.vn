<?php

namespace Modules\Product\Data\Requests;

use Modules\Product\Enums\DocumentGroupType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class StoreDocumentMasterTypeData extends Data
{
    public function __construct(
        #[Nullable, StringType, Max(60), Regex('/^[a-z0-9_]+$/'), Unique('document_master_types', 'code')]
        public readonly ?string $code,

        #[Required, StringType, Max(255)]
        public readonly string $name,

        public readonly DocumentGroupType $document_group,

        public readonly bool $is_required_issue_date = true,

        public readonly bool $is_required_expiry_date = false,

        #[Nullable, Min(1), Max(600)]
        public readonly ?int $default_validity_months,
    ) {}

    public static function messages(): array
    {
        return [
            'code.string'   => 'Mã loại giấy tờ không hợp lệ.',
            'code.max'      => 'Mã loại giấy tờ không được vượt quá 60 ký tự.',
            'code.regex'    => 'Mã loại giấy tờ chỉ được chứa chữ thường, số và dấu gạch dưới.',
            'code.unique'   => 'Mã loại giấy tờ này đã tồn tại.',

            'name.required' => 'Vui lòng nhập tên loại giấy tờ.',
            'name.string'   => 'Tên loại giấy tờ không hợp lệ.',
            'name.max'      => 'Tên loại giấy tờ không được vượt quá 255 ký tự.',

            'document_group.required' => 'Vui lòng chọn nhóm giấy tờ.',
            'document_group.enum'     => 'Nhóm giấy tờ không hợp lệ.',

            'default_validity_months.min' => 'Số tháng hiệu lực phải lớn hơn 0.',
            'default_validity_months.max' => 'Số tháng hiệu lực không hợp lệ.',
        ];
    }
}
