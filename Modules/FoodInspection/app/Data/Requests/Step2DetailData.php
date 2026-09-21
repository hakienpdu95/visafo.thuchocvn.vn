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

/** Một dòng món ăn trong Sổ kiểm thực Bước 2. */
class Step2DetailData extends Data
{
    public function __construct(
        public readonly MealTime $meal_time,

        #[Required, StringType, Max(255)]
        public readonly string $dish_name,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $main_ingredients = null,

        #[Nullable, Min(0), Max(1000000)]
        public readonly ?int $quantity = null,

        #[Nullable, DateFormat('H:i')]
        public readonly ?string $prep_time = null,

        #[Nullable, DateFormat('H:i')]
        public readonly ?string $cook_time = null,

        public readonly bool $hygiene_personnel = true,
        public readonly bool $hygiene_equipment = true,
        public readonly bool $hygiene_area = true,
        public readonly bool $sensory_eval = true,

        #[Nullable, StringType, Max(1000)]
        public readonly ?string $action_taken = null,

        #[Nullable, StringType, Exists('menu_dishes', 'id')]
        public readonly ?string $menu_dish_id = null,

        // Có khi sửa sổ: id dòng hiện có (cập nhật tại chỗ); trống = dòng mới.
        #[Nullable, StringType, Exists('food_inspection_step2_details', 'id')]
        public readonly ?string $id = null,
    ) {}

    public function isFailed(): bool
    {
        return ! ($this->hygiene_personnel && $this->hygiene_equipment && $this->hygiene_area && $this->sensory_eval);
    }
}
