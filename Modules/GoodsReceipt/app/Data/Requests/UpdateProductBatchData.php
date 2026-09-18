<?php

namespace Modules\GoodsReceipt\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Data;

class UpdateProductBatchData extends Data
{
    public function __construct(
        #[Nullable, Date]
        public readonly ?string $mfg_date,

        #[Nullable, Date]
        public readonly ?string $exp_date,
    ) {}

    public static function messages(): array
    {
        return [
            'mfg_date.date' => 'Ngày sản xuất không hợp lệ.',
            'exp_date.date' => 'Hạn sử dụng không hợp lệ.',
        ];
    }
}
