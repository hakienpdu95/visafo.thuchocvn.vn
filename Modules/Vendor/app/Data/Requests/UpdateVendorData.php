<?php

namespace Modules\Vendor\Data\Requests;

use Illuminate\Validation\Rule;
use Modules\Vendor\Enums\VendorStatus;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateVendorData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $name,

        #[Required, StringType, Regex('/^\d{10,13}$/')]
        public readonly string $tax_code,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $address,

        #[Nullable, StringType, Size(2), Exists('provinces', 'province_code')]
        public readonly ?string $province_code,

        #[Nullable, StringType, Size(5), Exists('wards', 'ward_code')]
        public readonly ?string $ward_code,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $phone_number,

        #[Nullable, Email, Max(100)]
        public readonly ?string $email,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $representative_name,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $representative_title,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $representative_phone,

        #[Nullable, Email, Max(100)]
        public readonly ?string $representative_email,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $contact_person_name,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $contact_person_title,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $contact_person_phone,

        #[Nullable, Email, Max(100)]
        public readonly ?string $contact_person_email,

        public readonly VendorStatus $status,
    ) {}

    public static function rules(): array
    {
        $currentId = request()->route('vendor')?->id;

        return [
            'tax_code' => [
                'required', 'string', 'regex:/^\d{10,13}$/',
                Rule::unique('vendors', 'tax_code')->ignore($currentId),
            ],
        ];
    }

    public static function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên nhà cung cấp.',
            'name.string'   => 'Tên nhà cung cấp không hợp lệ.',
            'name.max'      => 'Tên nhà cung cấp không được vượt quá 255 ký tự.',

            'tax_code.required' => 'Vui lòng nhập mã số thuế.',
            'tax_code.string'   => 'Mã số thuế không hợp lệ.',
            'tax_code.regex'    => 'Mã số thuế phải gồm 10 đến 13 chữ số.',
            'tax_code.unique'   => 'Mã số thuế này đã được đăng ký bởi nhà cung cấp khác.',

            'address.string' => 'Địa chỉ không hợp lệ.',
            'address.max'    => 'Địa chỉ không được vượt quá 500 ký tự.',

            'province_code.exists' => 'Tỉnh/thành phố không hợp lệ.',
            'ward_code.exists'     => 'Phường/xã không hợp lệ.',

            'phone_number.string' => 'Số điện thoại công ty không hợp lệ.',
            'phone_number.max'    => 'Số điện thoại công ty không được vượt quá 20 ký tự.',

            'email.string' => 'Email công ty không hợp lệ.',
            'email.email'  => 'Email công ty không đúng định dạng.',
            'email.max'    => 'Email công ty không được vượt quá 100 ký tự.',

            'representative_name.string'  => 'Tên người đại diện không hợp lệ.',
            'representative_name.max'     => 'Tên người đại diện không được vượt quá 100 ký tự.',
            'representative_title.max'    => 'Chức danh không được vượt quá 100 ký tự.',
            'representative_phone.max'    => 'Số điện thoại người đại diện không được vượt quá 20 ký tự.',
            'representative_email.email'  => 'Email người đại diện không đúng định dạng.',
            'representative_email.max'    => 'Email người đại diện không được vượt quá 100 ký tự.',

            'contact_person_name.max'    => 'Tên đầu mối liên hệ không được vượt quá 100 ký tự.',
            'contact_person_title.max'   => 'Chức vụ đầu mối liên hệ không được vượt quá 100 ký tự.',
            'contact_person_phone.max'   => 'Số điện thoại đầu mối liên hệ không được vượt quá 20 ký tự.',
            'contact_person_email.email' => 'Email đầu mối liên hệ không đúng định dạng.',
            'contact_person_email.max'   => 'Email đầu mối liên hệ không được vượt quá 100 ký tự.',

            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.enum'     => 'Trạng thái không hợp lệ.',
        ];
    }
}
