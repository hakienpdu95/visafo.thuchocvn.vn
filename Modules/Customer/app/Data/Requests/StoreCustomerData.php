<?php

namespace Modules\Customer\Data\Requests;

use Illuminate\Validation\Rule;
use Modules\Customer\Enums\CustomerGroup;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Enums\MealModel;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class StoreCustomerData extends Data
{
    public function __construct(
        #[Nullable, StringType, Max(50), Regex('/^[A-Za-z0-9\-]+$/'), Unique('customers', 'customer_code')]
        public readonly ?string $customer_code,

        #[Required, StringType, Max(255)]
        public readonly string $name,

        public readonly CustomerGroup $customer_group,

        public readonly MealModel $meal_model,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $tax_code,

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

        #[Nullable, Exists('employees', 'id')]
        public readonly ?string $pic_id,

        public readonly CustomerStatus $status = CustomerStatus::Active,
    ) {}

    public static function rules(): array
    {
        return [
            'customer_group' => ['required', Rule::enum(CustomerGroup::class)],
            'meal_model'     => ['required', Rule::enum(MealModel::class)],
        ];
    }

    public static function messages(): array
    {
        return [
            'customer_code.string' => 'Mã khách hàng không hợp lệ.',
            'customer_code.regex'  => 'Mã khách hàng chỉ được chứa chữ, số và dấu gạch ngang.',
            'customer_code.max'    => 'Mã khách hàng không được vượt quá 50 ký tự.',
            'customer_code.unique' => 'Mã khách hàng này đã tồn tại.',

            'name.required' => 'Vui lòng nhập tên khách hàng.',
            'name.string'   => 'Tên khách hàng không hợp lệ.',
            'name.max'      => 'Tên khách hàng không được vượt quá 255 ký tự.',

            'customer_group.required' => 'Vui lòng chọn nhóm khách hàng.',
            'customer_group.enum'     => 'Nhóm khách hàng không hợp lệ.',

            'meal_model.required' => 'Vui lòng chọn mô hình tổ chức bữa ăn.',
            'meal_model.enum'     => 'Mô hình tổ chức bữa ăn không hợp lệ.',

            'tax_code.string' => 'Mã số thuế không hợp lệ.',
            'tax_code.max'    => 'Mã số thuế không được vượt quá 20 ký tự.',

            'address.string' => 'Địa chỉ không hợp lệ.',
            'address.max'    => 'Địa chỉ không được vượt quá 500 ký tự.',

            'province_code.exists' => 'Tỉnh/thành phố không hợp lệ.',
            'ward_code.exists'     => 'Phường/xã không hợp lệ.',

            'phone_number.string' => 'Số điện thoại không hợp lệ.',
            'phone_number.max'    => 'Số điện thoại không được vượt quá 20 ký tự.',

            'email.string' => 'Email không hợp lệ.',
            'email.email'  => 'Email không đúng định dạng.',
            'email.max'    => 'Email không được vượt quá 100 ký tự.',

            'representative_name.string'  => 'Tên người đại diện không hợp lệ.',
            'representative_name.max'     => 'Tên người đại diện không được vượt quá 100 ký tự.',
            'representative_title.max'    => 'Chức danh không được vượt quá 100 ký tự.',
            'representative_phone.max'    => 'Số điện thoại người đại diện không được vượt quá 20 ký tự.',
            'representative_email.email'  => 'Email người đại diện không đúng định dạng.',
            'representative_email.max'    => 'Email người đại diện không được vượt quá 100 ký tự.',

            'pic_id.exists' => 'Nhân viên phụ trách không hợp lệ.',

            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.enum'     => 'Trạng thái không hợp lệ.',
        ];
    }
}
