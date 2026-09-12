<?php

namespace Modules\User\Data;

use App\Enums\RoleEnum;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreUserData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $name,

        public readonly ?string $email,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $username,

        public readonly string $password,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $department,

        public readonly string $system_role,

        #[Nullable, Exists('vendors', 'id')]
        public readonly ?string $vendor_id,

        #[Nullable, Exists('employees', 'id')]
        public readonly ?string $employee_id,

        public readonly bool $is_active = true,

        public readonly bool $send_welcome_email = false,
    ) {}

    public static function rules(): array
    {
        $allowedRoles = implode(',', array_column(RoleEnum::cases(), 'value'));

        $isEmployeeLinked = fn () => ! in_array(request()->input('system_role'), [
            RoleEnum::ADMIN->value, RoleEnum::FARMER->value,
        ], true);

        return [
            // Email luôn không bắt buộc — chỉ cần đúng định dạng email cơ bản
            // (không kiểm tra DNS/email công ty). Username mới là định danh đăng nhập bắt buộc.
            'email'       => [
                'nullable', 'email', 'max:255', Rule::unique('users', 'email'),
            ],
            'username'    => [
                'required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9._-]+$/',
                Rule::unique('users', 'username'),
            ],
            'password'    => [
                'required', 'string', 'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers(),
            ],
            'system_role' => ['required', 'string', "in:$allowedRoles"],
            'vendor_id'   => [
                Rule::requiredIf(fn () => request()->input('system_role') === RoleEnum::FARMER->value),
                'nullable', Rule::exists('vendors', 'id'),
            ],
            'employee_id' => [
                Rule::requiredIf($isEmployeeLinked),
                'nullable', Rule::exists('employees', 'id'),
            ],
        ];
    }

    public static function messages(): array
    {
        return [
            'email.unique'          => 'Email này đã được sử dụng.',
            'username.required'     => 'Tên đăng nhập là bắt buộc.',
            'username.regex'        => 'Tên đăng nhập chỉ gồm chữ, số, dấu chấm, gạch ngang, gạch dưới.',
            'username.unique'       => 'Tên đăng nhập này đã được sử dụng.',
            'password.min'          => 'Mật khẩu tối thiểu 8 ký tự.',
            'password.mixed_case'   => 'Mật khẩu phải có cả chữ HOA và chữ thường.',
            'password.letters'      => 'Mật khẩu phải chứa ít nhất một chữ cái.',
            'password.numbers'      => 'Mật khẩu phải chứa ít nhất một chữ số.',
            'password.confirmed'    => 'Xác nhận mật khẩu không khớp.',
            'system_role.in'        => 'Vai trò không hợp lệ.',
            'vendor_id.required'    => 'Vui lòng chọn Nông hộ liên kết cho vai trò này.',
            'vendor_id.exists'      => 'Nông hộ được chọn không hợp lệ.',
            'employee_id.required'  => 'Vui lòng chọn Hồ sơ nhân viên liên kết cho vai trò này.',
            'employee_id.exists'    => 'Hồ sơ nhân viên được chọn không hợp lệ.',
        ];
    }
}
