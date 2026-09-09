<?php

namespace Modules\Product\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\AfterOrEqual;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;

class StorePartnerProductComplianceData extends Data
{
    public function __construct(
        public readonly string $document_type_id,

        #[Required, StringType, Max(150)]
        public readonly string $document_number,

        #[Nullable, Date]
        public readonly ?string $issue_date,

        #[Nullable, Date, AfterOrEqual('issue_date')]
        public readonly ?string $expiration_date,

        #[Nullable, Url, Max(500)]
        public readonly ?string $file_url,
    ) {}

    public static function rules(): array
    {
        return [
            'document_type_id' => ['required', 'string', 'exists:document_master_types,id'],
        ];
    }

    public static function messages(): array
    {
        return [
            'document_type_id.required' => 'Vui lòng chọn loại giấy tờ.',
            'document_type_id.exists'   => 'Loại giấy tờ không hợp lệ.',

            'document_number.required' => 'Vui lòng nhập số hiệu giấy tờ.',
            'document_number.string'   => 'Số hiệu giấy tờ không hợp lệ.',
            'document_number.max'      => 'Số hiệu giấy tờ không được vượt quá 150 ký tự.',

            'issue_date.date' => 'Ngày cấp không hợp lệ.',

            'expiration_date.date'           => 'Ngày hết hạn không hợp lệ.',
            'expiration_date.after_or_equal' => 'Ngày hết hạn phải sau hoặc bằng ngày cấp.',

            'file_url.url' => 'Đường dẫn file không hợp lệ.',
            'file_url.max' => 'Đường dẫn file không được vượt quá 500 ký tự.',
        ];
    }
}
