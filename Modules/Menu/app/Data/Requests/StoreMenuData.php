<?php

namespace Modules\Menu\Data\Requests;

use Modules\Menu\Enums\MealTime;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreMenuData extends Data
{
    /** @param  array<int, MenuDishData>  $dishes */
    public function __construct(
        #[Required, StringType, Exists('customers', 'id')]
        public readonly string $customer_id,

        #[Required, Date]
        public readonly string $menu_date,

        public readonly MealTime $meal_time,

        #[Nullable, StringType, Max(2000)]
        public readonly ?string $note = null,

        #[Required, ArrayType, Min(1), DataCollectionOf(MenuDishData::class)]
        public readonly array $dishes = [],
    ) {}

    public static function messages(): array
    {
        return [
            'customer_id.required' => 'Vui lòng chọn cơ sở / doanh nghiệp suất ăn.',
            'customer_id.exists'   => 'Cơ sở được chọn không hợp lệ.',
            'menu_date.required'   => 'Vui lòng chọn ngày áp dụng thực đơn.',
            'menu_date.date'       => 'Ngày áp dụng không đúng định dạng ngày.',
            'meal_time.required'   => 'Vui lòng chọn bữa ăn.',
            'meal_time.enum'       => 'Bữa ăn không hợp lệ.',
            'note.max'             => 'Ghi chú không được vượt quá :max ký tự.',
            'dishes.required'      => 'Thực đơn cần ít nhất một món ăn.',
            'dishes.min'           => 'Thực đơn cần ít nhất một món ăn.',
            'dishes.*.dish_name.required' => 'Vui lòng nhập tên món ăn ở mọi dòng.',
            'dishes.*.dish_name.max'      => 'Tên món ăn không được vượt quá :max ký tự.',
            'dishes.*.main_ingredients.max' => 'Nguyên liệu chính không được vượt quá :max ký tự.',
            'dishes.*.servings.min' => 'Số suất ăn không được âm.',
            'dishes.*.servings.integer' => 'Số suất ăn phải là số nguyên.',
            'dishes.*.id.exists'   => 'Món ăn được sửa không tồn tại.',
        ];
    }
}
