<?php

namespace Modules\Employee\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreDepartmentData extends Data
{
    public function __construct(
        #[Required, StringType, Max(150)]
        public readonly string $name,

        public readonly bool $is_food_contact = false,
    ) {}

    public static function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên phòng ban.',
            'name.string'   => 'Tên phòng ban không hợp lệ.',
            'name.max'      => 'Tên phòng ban không được vượt quá 150 ký tự.',
        ];
    }
}
