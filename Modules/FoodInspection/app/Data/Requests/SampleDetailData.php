<?php

namespace Modules\FoodInspection\Data\Requests;

use Modules\Menu\Enums\MealTime;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** Một mẫu thức ăn lưu: Nhịp 1 (lấy mẫu) bắt buộc; Nhịp 2 (hủy mẫu) chỉ điền sau ≥ 24 giờ. */
class SampleDetailData extends Data
{
    public function __construct(
        public readonly MealTime $meal_time,

        #[Required, StringType, Max(255)]
        public readonly string $dish_name,

        #[Required, StringType, Max(20), Regex('/^\d+([.,]\d+)?\s*(g|ml|kg|l)$/i')]
        public readonly string $sample_volume,

        #[Required, Date]
        public readonly string $sampled_at,

        #[Required, StringType, Max(255)]
        public readonly string $sampler_name,

        #[Nullable, Min(0), Max(1000000)]
        public readonly ?int $portion_qty = null,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $container_type = null,

        #[Nullable, Min(-30), Max(60)]
        public readonly ?float $storage_temp = null,

        #[Nullable, Date]
        public readonly ?string $destroyed_at = null,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $destroyer_name = null,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $quality_note = null,

        #[Nullable, StringType, Exists('food_inspection_step3_details', 'id')]
        public readonly ?string $step3_detail_id = null,

        #[Nullable, StringType, Exists('menu_dishes', 'id')]
        public readonly ?string $menu_dish_id = null,

        // Có khi sửa phiếu: id mẫu hiện có (cập nhật tại chỗ); trống = mẫu mới.
        #[Nullable, StringType, Exists('food_sample_details', 'id')]
        public readonly ?string $id = null,
    ) {}

    public static function messages(): array
    {
        return [
            'sample_volume.regex' => 'Khối lượng/thể tích phải kèm đơn vị g hoặc ml (VD: 100g, 150ml).',
        ];
    }
}
