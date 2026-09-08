<?php

namespace Modules\Warehouse\Data\Requests;

use Illuminate\Validation\Rule;
use Modules\Warehouse\Enums\BatchDocumentType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;

class StoreBatchDocumentData extends Data
{
    public function __construct(
        public readonly BatchDocumentType $document_code,

        #[Required, StringType, Max(150)]
        public readonly string $document_number,

        #[Nullable, Url, Max(500)]
        public readonly ?string $file_url,
    ) {}

    public static function rules(): array
    {
        return [
            'document_code' => ['required', Rule::enum(BatchDocumentType::class)],
        ];
    }

    public static function messages(): array
    {
        return [
            'document_code.required' => 'Vui lòng chọn loại chứng từ.',
            'document_code.enum'     => 'Loại chứng từ không hợp lệ.',

            'document_number.required' => 'Vui lòng nhập số hiệu phiếu.',
            'document_number.string'   => 'Số hiệu phiếu không hợp lệ.',
            'document_number.max'      => 'Số hiệu phiếu không được vượt quá 150 ký tự.',

            'file_url.url' => 'Đường dẫn file không hợp lệ.',
            'file_url.max' => 'Đường dẫn file không được vượt quá 500 ký tự.',
        ];
    }
}
