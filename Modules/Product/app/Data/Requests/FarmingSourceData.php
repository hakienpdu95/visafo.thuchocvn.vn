<?php

namespace Modules\Product\Data\Requests;

use Modules\Product\Enums\AreaUnit;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\RequiredWith;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class FarmingSourceData extends Data
{
    public function __construct(
        #[Required, Exists('vendors', 'id')]
        public readonly string $vendor_id,

        #[Required, StringType, Max(255)]
        public readonly string $name,

        #[Nullable, Numeric, Min(0), Max(99999999)]
        public readonly ?float $area_value,

        #[Nullable, RequiredWith('area_value'), Enum(AreaUnit::class)]
        public readonly ?string $area_unit,

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
            'area_value.numeric'       => 'Diện tích phải là số.',
            'area_value.min'           => 'Diện tích không được âm.',
            'area_unit.required_with'  => 'Vui lòng chọn đơn vị diện tích.',
            'area_unit.enum'           => 'Đơn vị diện tích không hợp lệ.',
        ];
    }

    public function areaAttributes(): array
    {
        $unit = $this->area_value !== null ? AreaUnit::from($this->area_unit) : null;

        return [
            'area_value'   => $unit ? $this->area_value : null,
            'area_unit'    => $unit?->value,
            'area_hectare' => $unit?->toHectare($this->area_value),
        ];
    }
}
