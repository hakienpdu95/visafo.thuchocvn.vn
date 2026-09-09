<?php

namespace Modules\Warehouse\Data\Requests;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateOutboundOrderData extends Data
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
        $currentId = request()->route('order')?->id;

        return [
            'order_number' => [
                'required', 'string', 'max:100',
                Rule::unique('outbound_orders', 'order_number')
                    ->ignore($currentId),
            ],
        ];
    }

    public static function messages(): array
    {
        return StoreOutboundOrderData::messages();
    }
}
