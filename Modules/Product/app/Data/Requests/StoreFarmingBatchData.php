<?php

namespace Modules\Product\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class StoreFarmingBatchData extends Data
{
    public function __construct(
        #[Required, Exists('farming_sources', 'id')]
        public readonly string $farming_source_id,

        #[Required, Exists('agri_seeds', 'id')]
        public readonly string $agri_seed_id,

        #[Required, Exists('partner_products', 'id')]
        public readonly string $partner_product_id,

        #[Required, StringType, Max(60), Unique('farming_batches', 'batch_code')]
        public readonly string $batch_code,

        #[Nullable, Date]
        public readonly ?string $sowing_date,

        #[Nullable, Date]
        public readonly ?string $expected_harvest_date,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $notes,
    ) {}

    public static function messages(): array
    {
        return [
            'farming_source_id.required' => 'Vui lòng chọn vùng trồng.',
            'farming_source_id.exists'   => 'Vùng trồng được chọn không hợp lệ.',
            'agri_seed_id.required'      => 'Vui lòng chọn giống cây trồng.',
            'agri_seed_id.exists'        => 'Giống cây trồng được chọn không hợp lệ.',
            'partner_product_id.required' => 'Vui lòng chọn mặt hàng thương mại.',
            'partner_product_id.exists'   => 'Mặt hàng được chọn không hợp lệ.',
            'batch_code.required'        => 'Vui lòng nhập mã lô.',
            'batch_code.unique'          => 'Mã lô này đã tồn tại.',
            'expected_harvest_date.after_or_equal' => 'Ngày dự kiến thu hoạch phải sau hoặc bằng ngày gieo.',
        ];
    }
}
