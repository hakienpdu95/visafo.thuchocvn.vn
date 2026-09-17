<?php

namespace Modules\Compliance\Data\Requests;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Modules\Compliance\Enums\SharedDocumentCategory;
use Spatie\LaravelData\Attributes\Validation\File;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Mimes;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreSharedComplianceDocumentData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $custom_name,

        public readonly SharedDocumentCategory $custom_category,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $notes,

        #[Required, File, Mimes(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png']), Max(10240)]
        public readonly UploadedFile $file,
    ) {}

    public static function rules(): array
    {
        return [
            'custom_category' => ['required', Rule::enum(SharedDocumentCategory::class)],
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

            'file.required' => 'Vui lòng chọn file đính kèm.',
            'file.file'     => 'Tệp tải lên không hợp lệ.',
            'file.mimes'    => 'Chỉ chấp nhận file PDF, DOCX, XLSX hoặc JPG.',
            'file.max'      => 'Dung lượng file không được vượt quá 10MB.',
        ];
    }
}
