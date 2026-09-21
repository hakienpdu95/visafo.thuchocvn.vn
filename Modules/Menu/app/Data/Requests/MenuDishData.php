<?php

namespace Modules\Menu\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class MenuDishData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $dish_name,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $main_ingredients = null,

        #[Nullable, Min(0), Max(1000000)]
        public readonly ?int $servings = null,

        // Có khi sửa thực đơn: id món hiện có (giữ nguyên id để các sổ kiểm thực đã liên kết không bị mất liên kết).
        #[Nullable, StringType, Exists('menu_dishes', 'id')]
        public readonly ?string $id = null,
    ) {}
}
