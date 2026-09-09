<?php

namespace Modules\Recall\Data\Requests;

use Illuminate\Validation\Rule;
use Modules\Recall\Enums\RecallSeverity;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreProductRecallData extends Data
{
    public function __construct(
        #[Required]
        public readonly string $product_id,

        #[Nullable]
        public readonly ?string $batch_id,

        #[Required, StringType]
        public readonly string $reason,

        public readonly ?RecallSeverity $severity,

        #[Nullable, StringType]
        public readonly ?string $notes,
    ) {}

    public static function rules(): array
    {
        return [
            'product_id' => ['required', Rule::exists('products', 'id')],
            'batch_id'   => ['nullable', Rule::exists('batches', 'id')->where('product_id', request('product_id'))],
            'severity'   => ['nullable', Rule::enum(RecallSeverity::class)],
        ];
    }

    public static function messages(): array
    {
        return [
            'product_id.required' => 'Vui lòng chọn sản phẩm.',
            'product_id.exists'   => 'Sản phẩm không hợp lệ.',

            'batch_id.exists' => 'Lô hàng không hợp lệ hoặc không thuộc sản phẩm đã chọn.',

            'reason.required' => 'Vui lòng nhập lý do thu hồi.',
            'reason.string'   => 'Lý do thu hồi không hợp lệ.',

            'severity.enum' => 'Mức độ nghiêm trọng không hợp lệ.',
        ];
    }
}
