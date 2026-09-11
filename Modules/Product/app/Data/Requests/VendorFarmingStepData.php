<?php

namespace Modules\Product\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class VendorFarmingStepData extends Data
{
    public function __construct(
        #[Nullable, Exists('partner_products', 'id')]
        public readonly ?string $partner_product_id,

        #[Required, StringType, Max(100)]
        public readonly string $step_name,

        #[Required, In('cultivation', 'other')]
        public readonly string $base_activity_type,

        #[Nullable, IntegerType]
        public readonly ?int $order_index,
    ) {}

    public static function messages(): array
    {
        return [
            'partner_product_id.exists' => 'Mặt hàng được chọn không hợp lệ.',

            'step_name.required' => 'Vui lòng nhập tên công đoạn.',
            'step_name.max'       => 'Tên công đoạn không được vượt quá 100 ký tự.',

            'base_activity_type.required' => 'Vui lòng chọn phân loại gốc.',
            'base_activity_type.in'       => 'Phân loại gốc chỉ được là "cultivation" hoặc "other" để không ảnh hưởng thuật toán ATTP.',
        ];
    }
}
