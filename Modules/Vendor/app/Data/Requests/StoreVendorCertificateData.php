<?php

namespace Modules\Vendor\Data\Requests;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Modules\Vendor\Enums\VendorCertificateType;
use Spatie\LaravelData\Attributes\Validation\AfterOrEqual;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\File;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Mimes;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreVendorCertificateData extends Data
{
    public function __construct(
        public readonly VendorCertificateType $certificate_type,

        #[Required, StringType, Max(100)]
        public readonly string $certificate_number,

        #[Required, Date]
        public readonly string $issue_date,

        #[Nullable, Date, AfterOrEqual('issue_date')]
        public readonly ?string $expiry_date,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $issued_by,

        #[Nullable, Date]
        public readonly ?string $renewal_deadline,

        #[Nullable, File, Mimes(['pdf', 'jpg', 'jpeg', 'png']), Max(10240)]
        public readonly ?UploadedFile $file,
    ) {}

    public static function rules(): array
    {
        return [
            'certificate_type' => ['required', Rule::enum(VendorCertificateType::class)],
        ];
    }

    public static function messages(): array
    {
        return [
            'certificate_type.required' => 'Vui lòng chọn loại chứng chỉ.',
            'certificate_type.enum'     => 'Loại chứng chỉ không hợp lệ.',

            'certificate_number.required' => 'Vui lòng nhập số hiệu chứng chỉ.',
            'certificate_number.string'   => 'Số hiệu chứng chỉ không hợp lệ.',
            'certificate_number.max'      => 'Số hiệu chứng chỉ không được vượt quá 100 ký tự.',

            'issue_date.required' => 'Vui lòng chọn ngày cấp.',
            'issue_date.string'   => 'Ngày cấp không hợp lệ.',
            'issue_date.date'     => 'Ngày cấp không hợp lệ.',

            'expiry_date.string'         => 'Ngày hết hạn không hợp lệ.',
            'expiry_date.date'           => 'Ngày hết hạn không hợp lệ.',
            'expiry_date.after_or_equal' => 'Ngày hết hạn phải sau hoặc bằng ngày cấp.',

            'issued_by.string' => 'Nơi cấp không hợp lệ.',
            'issued_by.max'    => 'Nơi cấp không được vượt quá 255 ký tự.',

            'renewal_deadline.string' => 'Hạn gia hạn không hợp lệ.',
            'renewal_deadline.date'   => 'Hạn gia hạn không hợp lệ.',

            'file.file'  => 'Tệp tải lên không hợp lệ.',
            'file.mimes' => 'Chỉ chấp nhận file PDF, JPG hoặc PNG.',
            'file.max'   => 'Dung lượng file không được vượt quá 10MB.',
        ];
    }
}
