<?php

namespace Modules\FoodInspection\Data\Requests;

use Modules\Menu\Enums\MealTime;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** Một dòng món ăn trong Sổ kiểm thực Bước 3 (kiểm tra trước khi ăn). */
class Step3DetailData extends Data
{
    public function __construct(
        public readonly MealTime $meal_time,

        #[Required, StringType, Max(255)]
        public readonly string $dish_name,

        #[Nullable, Min(0), Max(1000000)]
        public readonly ?int $quantity = null,

        #[Nullable, DateFormat('H:i')]
        public readonly ?string $portion_time = null,

        #[Nullable, DateFormat('H:i')]
        public readonly ?string $eat_time = null,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $equipment_used = null,

        public readonly bool $sensory_eval = true,

        #[Nullable, StringType, Max(1000)]
        public readonly ?string $action_taken = null,

        #[Nullable, StringType, Exists('food_inspection_step2_details', 'id')]
        public readonly ?string $step2_detail_id = null,

        #[Nullable, StringType, Exists('menu_dishes', 'id')]
        public readonly ?string $menu_dish_id = null,

        // Có khi sửa sổ: id dòng hiện có (cập nhật tại chỗ); trống = dòng mới.
        #[Nullable, StringType, Exists('food_inspection_step3_details', 'id')]
        public readonly ?string $id = null,
    ) {}

    public function isFailed(): bool
    {
        return ! $this->sensory_eval;
    }
}
