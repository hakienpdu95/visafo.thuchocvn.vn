<?php

namespace Modules\Employee\Data\Requests;

use Illuminate\Http\UploadedFile;
use Modules\Employee\Enums\Gender;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\File;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Mimes;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;

class StoreEmployeeData extends Data
{
    public function __construct(
        #[Required, StringType, Max(150)]
        public readonly string $full_name,

        #[Nullable, Enum(Gender::class)]
        public readonly ?string $gender,

        #[Nullable, Date]
        public readonly ?string $dob,

        #[Nullable, Email, Max(150)]
        public readonly ?string $email,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $phone,

        #[Nullable, Url, Max(255)]
        public readonly ?string $facebook_url,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $job_title,

        #[Nullable, Date]
        public readonly ?string $start_date,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $address,

        #[Nullable, Regex('/^\d{12}$/')]
        public readonly ?string $id_card_number,

        #[Nullable, Date]
        public readonly ?string $id_card_issue_date,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $id_card_issue_place,

        public readonly array $department_ids = [],

        #[Nullable, File, Mimes(['jpg', 'jpeg', 'png', 'webp']), Max(5120)]
        public readonly ?UploadedFile $avatar = null,

        #[Nullable, File, Mimes(['jpg', 'jpeg', 'png', 'webp']), Max(10240)]
        public readonly ?UploadedFile $id_card_front = null,

        #[Nullable, File, Mimes(['jpg', 'jpeg', 'png', 'webp']), Max(10240)]
        public readonly ?UploadedFile $id_card_back = null,
    ) {}

    public function profileAttributes(): array
    {
        return [
            'full_name'           => $this->full_name,
            'gender'              => $this->gender,
            'dob'                 => $this->dob,
            'email'               => $this->email,
            'phone'               => $this->phone,
            'facebook_url'        => $this->facebook_url,
            'address'             => $this->address,
            'job_title'           => $this->job_title,
            'start_date'          => $this->start_date,
            'id_card_number'      => $this->id_card_number,
            'id_card_issue_date'  => $this->id_card_issue_date,
            'id_card_issue_place' => $this->id_card_issue_place,
        ];
    }

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

            'gender.enum' => 'Giới tính không hợp lệ.',

            'dob.date'   => 'Ngày sinh không hợp lệ.',

            'start_date.date' => 'Ngày bắt đầu làm việc không hợp lệ.',

            'address.max' => 'Địa chỉ không được vượt quá 500 ký tự.',

            'id_card_number.regex' => 'Số CCCD phải đủ 12 chữ số.',

            'id_card_issue_date.date'            => 'Ngày cấp CCCD không hợp lệ.',

            'id_card_issue_place.max' => 'Nơi cấp không được vượt quá 255 ký tự.',

            'id_card_front.file'  => 'Ảnh CCCD mặt trước không hợp lệ.',
            'id_card_front.mimes' => 'Ảnh CCCD mặt trước chỉ chấp nhận JPG, PNG hoặc WEBP.',
            'id_card_front.max'   => 'Ảnh CCCD mặt trước không được vượt quá 10MB.',
            'id_card_back.file'   => 'Ảnh CCCD mặt sau không hợp lệ.',
            'id_card_back.mimes'  => 'Ảnh CCCD mặt sau chỉ chấp nhận JPG, PNG hoặc WEBP.',
            'id_card_back.max'    => 'Ảnh CCCD mặt sau không được vượt quá 10MB.',

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
