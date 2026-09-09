<?php

namespace Modules\Employee\Data\Requests;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Modules\Employee\Enums\HealthRecordType;
use Spatie\LaravelData\Attributes\Validation\AfterOrEqual;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\File;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Mimes;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreEmployeeHealthRecordData extends Data
{
    public function __construct(
        public readonly HealthRecordType $record_type,

        #[Required, Date]
        public readonly string $issue_date,

        #[Nullable, Date, AfterOrEqual('issue_date')]
        public readonly ?string $expiry_date,

        #[Nullable, StringType, Max(30)]
        public readonly ?string $result,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $certificate_number,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $issued_by,

        #[Nullable, StringType, Max(1000)]
        public readonly ?string $notes,

        #[Nullable, File, Mimes(['pdf', 'jpg', 'jpeg', 'png']), Max(10240)]
        public readonly ?UploadedFile $file,
    ) {}

    public static function rules(): array
    {
        return [
            'record_type' => ['required', Rule::enum(HealthRecordType::class)],
        ];
    }

    public static function messages(): array
    {
        return [
            'record_type.required' => 'Vui lòng chọn loại hồ sơ.',
            'record_type.enum'     => 'Loại hồ sơ không hợp lệ.',

            'issue_date.required' => 'Vui lòng chọn ngày khám/ngày cấp.',
            'issue_date.date'     => 'Ngày khám/ngày cấp không hợp lệ.',

            'expiry_date.date'           => 'Ngày hết hạn không hợp lệ.',
            'expiry_date.after_or_equal' => 'Ngày hết hạn phải sau hoặc bằng ngày khám/ngày cấp.',

            'result.string' => 'Kết luận không hợp lệ.',
            'result.max'    => 'Kết luận không được vượt quá 30 ký tự.',

            'certificate_number.string' => 'Số giấy xác nhận không hợp lệ.',
            'certificate_number.max'    => 'Số giấy xác nhận không được vượt quá 100 ký tự.',

            'issued_by.string' => 'Cơ quan cấp không hợp lệ.',
            'issued_by.max'    => 'Cơ quan cấp không được vượt quá 255 ký tự.',

            'notes.max' => 'Ghi chú không được vượt quá 1000 ký tự.',

            'file.file'  => 'Tệp tải lên không hợp lệ.',
            'file.mimes' => 'Chỉ chấp nhận file PDF, JPG hoặc PNG.',
            'file.max'   => 'Dung lượng file không được vượt quá 10MB.',
        ];
    }
}
