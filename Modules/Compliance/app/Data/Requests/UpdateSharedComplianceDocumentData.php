<?php

namespace Modules\Compliance\Data\Requests;

use Illuminate\Validation\Rule;
use Modules\Compliance\Enums\SharedDocumentCategory;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateSharedComplianceDocumentData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $custom_name,

        public readonly SharedDocumentCategory $custom_category,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $notes,

        /** @var array<int, \Illuminate\Http\UploadedFile> — tệp mới, luôn gộp thêm chứ không thay thế */
        public readonly array $files = [],
    ) {}

    public static function rules(): array
    {
        return [
            'custom_category' => ['required', Rule::enum(SharedDocumentCategory::class)],

            'files'   => ['nullable', 'array'],
            'files.*' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public static function messages(): array
    {
        return [
            'custom_name.required' => 'Vui lòng nhập tên tài liệu.',
            'custom_name.max'      => 'Tên tài liệu không được vượt quá 255 ký tự.',

            'custom_category.required' => 'Vui lòng chọn nhóm/phân loại.',
            'custom_category.enum'     => 'Nhóm/phân loại không hợp lệ.',

            'notes.max' => 'Ghi chú không được vượt quá 500 ký tự.',

            'files.*.file'  => 'Tệp tải lên không hợp lệ.',
            'files.*.mimes' => 'Chỉ chấp nhận file PDF, DOCX, XLSX hoặc JPG.',
            'files.*.max'   => 'Dung lượng mỗi file không được vượt quá 10MB.',
        ];
    }
}
