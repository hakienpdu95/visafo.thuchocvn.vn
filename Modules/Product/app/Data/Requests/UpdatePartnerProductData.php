<?php

namespace Modules\Product\Data\Requests;

use Illuminate\Validation\Rule;
use Modules\Product\Enums\PartnerProductStatus;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdatePartnerProductData extends Data
{
    public function __construct(
        #[Required]
        public readonly string $vendor_id,

        #[Required]
        public readonly string $product_id,

        #[Required, StringType, Max(255)]
        public readonly string $name,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $manufacturer_name,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $origin_address,

        public readonly PartnerProductStatus $status = PartnerProductStatus::Active,
    ) {}

    public static function rules(): array
    {
        return [
            'vendor_id'  => ['required', Rule::exists('vendors', 'id')],
            'product_id' => ['required', Rule::exists('products', 'id')],
            'status'     => ['required', Rule::enum(PartnerProductStatus::class)],
        ];
    }

    public static function messages(): array
    {
        return StorePartnerProductData::messages();
    }
}
