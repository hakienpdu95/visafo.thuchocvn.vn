<?php

namespace Modules\Compliance\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class InternalFacilityData extends Data
{
    public function __construct(
        #[Required, StringType, Max(150)]
        public readonly string $name,

        #[Required, In('headquarter', 'farm', 'warehouse', 'processing_zone')]
        public readonly string $type,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $address,

        #[Required, In('active', 'inactive')]
        public readonly string $status,
    ) {}

    public static function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên cơ sở.',
            'name.max'       => 'Tên cơ sở không được vượt quá 150 ký tự.',

            'type.required' => 'Vui lòng chọn loại cơ sở.',
            'type.in'        => 'Loại cơ sở không hợp lệ.',

            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in'       => 'Trạng thái không hợp lệ.',
        ];
    }
}
