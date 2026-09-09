<?php

namespace Modules\Warehouse\Data\Requests;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class AddPickedBatchData extends Data
{
    public function __construct(
        #[Required]
        public readonly string $batch_id,

        #[Required, Min(1)]
        public readonly int $quantity,
    ) {}

    public static function rules(): array
    {
        return [
            'batch_id' => ['required', Rule::exists('batches', 'id')],
        ];
    }

    public static function messages(): array
    {
        return [
            'batch_id.required' => 'Vui lòng chọn lô hàng.',
            'batch_id.exists'   => 'Lô hàng không hợp lệ.',

            'quantity.required' => 'Vui lòng nhập số lượng.',
            'quantity.min'      => 'Số lượng phải lớn hơn 0.',
        ];
    }
}
