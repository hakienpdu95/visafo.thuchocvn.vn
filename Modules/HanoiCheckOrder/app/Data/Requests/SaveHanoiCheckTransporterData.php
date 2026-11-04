<?php

namespace Modules\HanoiCheckOrder\Data\Requests;

use Spatie\LaravelData\Data;

class SaveHanoiCheckTransporterData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $license_plate,
        public readonly ?string $code = null,
        public readonly ?string $phone = null,
        public readonly ?string $cccd = null,
        public readonly ?string $date_of_birth = null,
        public readonly ?string $address = null,
        public readonly ?string $transport_mean = null,
        public readonly bool $is_active = true,
    ) {}

    public static function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'license_plate'  => ['required', 'string', 'max:255'],
            'code'           => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_-]+$/'],
            'phone'          => ['nullable', 'string', 'max:20'],
            'cccd'           => ['nullable', 'string', 'max:20'],
            'date_of_birth'  => ['nullable', 'date', 'before:today'],
            'address'        => ['nullable', 'string', 'max:255'],
            'transport_mean' => ['nullable', 'string', 'max:255'],
            'is_active'      => ['boolean'],
        ];
    }

    public static function messages(): array
    {
        return [
            'name.required'          => 'Vui lòng nhập họ tên.',
            'license_plate.required' => 'HanoiCheck bắt buộc biển số phương tiện với nhân sự giao hàng.',
            'code.regex'             => 'Mã nhân sự chỉ gồm chữ không dấu, số, _ và -.',
            'phone.max'              => 'Số điện thoại tối đa :max ký tự.',
            'cccd.max'               => 'Số CCCD tối đa :max ký tự.',
        ];
    }
}
