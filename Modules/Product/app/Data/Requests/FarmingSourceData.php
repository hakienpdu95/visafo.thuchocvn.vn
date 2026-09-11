<?php

namespace Modules\Product\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class FarmingSourceData extends Data
{
    public function __construct(
        #[Required, Exists('vendors', 'id')]
        public readonly string $vendor_id,

        #[Required, StringType, Max(255)]
        public readonly string $name,

        #[Nullable, Numeric]
        public readonly ?float $area_hectare,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $water_source,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $address,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $notes,
    ) {}

    public static function messages(): array
    {
        return [
            'vendor_id.required' => 'Vui lòng chọn nông hộ.',
            'vendor_id.exists'   => 'Nông hộ được chọn không hợp lệ.',
            'name.required'      => 'Vui lòng nhập tên/mô tả vùng trồng.',
            'name.max'           => 'Tên vùng trồng không được vượt quá :max ký tự.',
            'area_hectare.numeric' => 'Diện tích phải là số.',
        ];
    }
}
