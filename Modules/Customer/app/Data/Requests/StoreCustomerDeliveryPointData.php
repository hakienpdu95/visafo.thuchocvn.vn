<?php

namespace Modules\Customer\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreCustomerDeliveryPointData extends Data
{
    public function __construct(
        #[Required, StringType, Max(150)]
        public readonly string $site_name,

        #[Required, StringType, Max(500)]
        public readonly string $address,

        #[Nullable, StringType, Size(2), Exists('provinces', 'province_code')]
        public readonly ?string $province_code,

        #[Nullable, StringType, Size(5), Exists('wards', 'ward_code')]
        public readonly ?string $ward_code,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $receiver_name,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $receiver_phone,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $note,
    ) {}

    public static function messages(): array
    {
        return [
            'site_name.required' => 'Vui lòng nhập tên cơ sở/điểm giao hàng.',
            'site_name.string'   => 'Tên cơ sở không hợp lệ.',
            'site_name.max'      => 'Tên cơ sở không được vượt quá 150 ký tự.',

            'address.required' => 'Vui lòng nhập địa chỉ giao hàng.',
            'address.string'   => 'Địa chỉ không hợp lệ.',
            'address.max'      => 'Địa chỉ không được vượt quá 500 ký tự.',

            'province_code.exists' => 'Tỉnh/thành phố không hợp lệ.',
            'ward_code.exists'     => 'Phường/xã không hợp lệ.',

            'receiver_name.string' => 'Tên người nhận hàng không hợp lệ.',
            'receiver_name.max'    => 'Tên người nhận hàng không được vượt quá 100 ký tự.',

            'receiver_phone.string' => 'Số điện thoại người nhận hàng không hợp lệ.',
            'receiver_phone.max'    => 'Số điện thoại người nhận hàng không được vượt quá 20 ký tự.',

            'note.string' => 'Ghi chú không hợp lệ.',
            'note.max'    => 'Ghi chú không được vượt quá 255 ký tự.',
        ];
    }
}
