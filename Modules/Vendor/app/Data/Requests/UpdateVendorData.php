<?php

namespace Modules\Vendor\Data\Requests;

use Illuminate\Validation\Rule;
use Modules\Vendor\Enums\VendorStatus;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateVendorData extends Data
{
    public function __construct(
        #[Nullable, StringType, Max(50), Regex('/^[A-Za-z0-9\-]+$/')]
        public readonly ?string $vendor_code,

        #[Required, StringType, Max(255)]
        public readonly string $name,

        #[Required, StringType, Max(50)]
        public readonly string $tax_code,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $address,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $phone_number,

        #[Nullable, Email, Max(100)]
        public readonly ?string $email,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $representative_name,

        public readonly VendorStatus $status,
    ) {}

    public static function rules(): array
    {
        $currentId = request()->route('vendor')?->id;

        return [
            'vendor_code' => [
                'nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-]+$/',
                Rule::unique('vendors', 'vendor_code')->ignore($currentId),
            ],
            'tax_code' => [
                'required', 'string', 'max:50',
                Rule::unique('vendors', 'tax_code')->ignore($currentId),
            ],
        ];
    }

    public static function messages(): array
    {
        return [
            'vendor_code.string' => 'Mã nhà cung cấp không hợp lệ.',
            'vendor_code.regex'  => 'Mã nhà cung cấp chỉ được chứa chữ, số và dấu gạch ngang.',
            'vendor_code.max'    => 'Mã nhà cung cấp không được vượt quá 50 ký tự.',
            'vendor_code.unique' => 'Mã nhà cung cấp này đã tồn tại.',

            'name.required' => 'Vui lòng nhập tên nhà cung cấp.',
            'name.string'   => 'Tên nhà cung cấp không hợp lệ.',
            'name.max'      => 'Tên nhà cung cấp không được vượt quá 255 ký tự.',

            'tax_code.required' => 'Vui lòng nhập mã số thuế.',
            'tax_code.string'   => 'Mã số thuế không hợp lệ.',
            'tax_code.max'      => 'Mã số thuế không được vượt quá 50 ký tự.',
            'tax_code.unique'   => 'Mã số thuế này đã được đăng ký bởi nhà cung cấp khác.',

            'address.string' => 'Địa chỉ không hợp lệ.',
            'address.max'    => 'Địa chỉ không được vượt quá 500 ký tự.',

            'phone_number.string' => 'Số điện thoại không hợp lệ.',
            'phone_number.max'    => 'Số điện thoại không được vượt quá 20 ký tự.',

            'email.string' => 'Email không hợp lệ.',
            'email.email'  => 'Email không đúng định dạng.',
            'email.max'    => 'Email không được vượt quá 100 ký tự.',

            'representative_name.string' => 'Tên người đại diện không hợp lệ.',
            'representative_name.max'    => 'Tên người đại diện không được vượt quá 100 ký tự.',

            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.enum'     => 'Trạng thái không hợp lệ.',
        ];
    }
}
