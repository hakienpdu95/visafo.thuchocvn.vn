<?php

namespace Modules\SalesPackage\Data\Requests;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\Validation\File;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Mimes;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateSalesPackageItemData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $custom_name,

        #[Nullable, File, Mimes(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png']), Max(10240)]
        public readonly ?UploadedFile $file,
    ) {}

    public static function messages(): array
    {
        return [
            'custom_name.required' => 'Vui lòng nhập tên tài liệu.',
            'custom_name.max'      => 'Tên tài liệu không được vượt quá 255 ký tự.',

            'file.file'  => 'Tệp tải lên không hợp lệ.',
            'file.mimes' => 'File phải là định dạng PDF, DOC, DOCX, XLS, XLSX, JPG hoặc PNG.',
            'file.max'   => 'File không được vượt quá 10MB.',
        ];
    }
}
