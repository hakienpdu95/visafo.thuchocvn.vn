<?php

namespace Modules\Employee\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateDepartmentData extends Data
{
    public function __construct(
        #[Required, StringType, Max(150)]
        public readonly string $name,

        public readonly bool $is_food_contact,
    ) {}

    public static function messages(): array
    {
        return StoreDepartmentData::messages();
    }
}
