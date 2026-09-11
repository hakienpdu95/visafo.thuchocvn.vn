<?php

namespace Modules\Product\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\Boolean;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Data;

class UpdateAgriPesticideData extends Data
{
    public function __construct(
        #[Nullable, IntegerType, Min(0)]
        public readonly ?int $quarantine_days,

        #[Boolean]
        public readonly bool $is_banned,
    ) {}

    public static function messages(): array
    {
        return [
            'quarantine_days.integer' => 'Số ngày cách ly phải là số nguyên.',
            'quarantine_days.min'     => 'Số ngày cách ly không được âm.',
        ];
    }
}
