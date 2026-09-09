<?php

namespace Modules\Warehouse\Data\Requests;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreOutboundOrderData extends Data
{
    public function __construct(
        #[Required, StringType, Max(100)]
        public readonly string $order_number,

        #[Required, StringType, Max(255)]
        public readonly string $dealer_name,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $dealer_phone,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $dealer_address,

        #[Required, Date]
        public readonly string $ordered_at,

        #[Nullable, StringType]
        public readonly ?string $notes,
    ) {}

    public static function rules(): array
    {
        return [
            'order_number' => [
                'required', 'string', 'max:100',
                Rule::unique('outbound_orders', 'order_number'),
            ],
        ];
    }

    public static function messages(): array
    {
        return [
            'order_number.required' => 'Vui lòng nhập mã đơn xuất buôn.',
            'order_number.unique'   => 'Mã đơn xuất buôn này đã tồn tại.',

            'dealer_name.required' => 'Vui lòng nhập tên đại lý.',

            'ordered_at.required' => 'Vui lòng chọn ngày lập đơn.',
            'ordered_at.date'     => 'Ngày lập đơn không hợp lệ.',
        ];
    }
}
