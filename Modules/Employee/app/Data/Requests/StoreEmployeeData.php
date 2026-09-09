<?php

namespace Modules\Employee\Data\Requests;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\File;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Mimes;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;

class StoreEmployeeData extends Data
{
    public function __construct(
        #[Required, StringType, Max(150)]
        public readonly string $full_name,

        #[Nullable, Email, Max(150)]
        public readonly ?string $email,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $phone,

        #[Nullable, Url, Max(255)]
        public readonly ?string $facebook_url,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $job_title,

        public readonly array $department_ids = [],

        #[Nullable, File, Mimes(['jpg', 'jpeg', 'png', 'webp']), Max(5120)]
        public readonly ?UploadedFile $avatar = null,
    ) {}

    public static function rules(): array
    {
        return [
            'department_ids'   => ['nullable', 'array'],
            'department_ids.*' => ['string', 'exists:departments,id'],
        ];
    }

    public static function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ và tên.',
            'full_name.string'   => 'Họ và tên không hợp lệ.',
            'full_name.max'      => 'Họ và tên không được vượt quá 150 ký tự.',

            'email.email' => 'Email không đúng định dạng.',
            'email.max'   => 'Email không được vượt quá 150 ký tự.',

            'phone.string' => 'Số điện thoại không hợp lệ.',
            'phone.max'    => 'Số điện thoại không được vượt quá 20 ký tự.',

            'facebook_url.url' => 'Đường dẫn Facebook không hợp lệ.',
            'facebook_url.max' => 'Đường dẫn Facebook không được vượt quá 255 ký tự.',

            'job_title.string' => 'Vai trò công việc không hợp lệ.',
            'job_title.max'    => 'Vai trò công việc không được vượt quá 100 ký tự.',

            'department_ids.array'   => 'Danh sách phòng ban không hợp lệ.',
            'department_ids.*.exists' => 'Một trong các phòng ban đã chọn không tồn tại.',

            'avatar.file'  => 'Tệp ảnh đại diện không hợp lệ.',
            'avatar.mimes' => 'Ảnh đại diện chỉ chấp nhận JPG, PNG hoặc WEBP.',
            'avatar.max'   => 'Ảnh đại diện không được vượt quá 5MB.',
        ];
    }
}
