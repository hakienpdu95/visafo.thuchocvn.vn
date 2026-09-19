<?php

namespace Modules\GoodsReceipt\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\ArrayType;
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

        /** @var array<int, array{key: string, value: ?string}>|null */
        #[Nullable, ArrayType]
        public readonly ?array $extra_attributes = null,
    ) {}

    public static function rules(): array
    {
        return [
            'extra_attributes'         => ['nullable', 'array', 'max:30'],
            'extra_attributes.*.key'   => ['required', 'string', 'max:100', 'distinct'],
            'extra_attributes.*.value' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public static function messages(): array
    {
        return [
            'mfg_date.date' => 'Ngày sản xuất không hợp lệ.',
            'exp_date.date' => 'Hạn sử dụng không hợp lệ.',
            'extra_attributes.max'             => 'Tối đa 30 dòng thông tin bổ sung.',
            'extra_attributes.*.key.required'  => 'Vui lòng nhập tên thông tin cho mọi dòng bổ sung.',
            'extra_attributes.*.key.max'       => 'Tên thông tin không được vượt quá 100 ký tự.',
            'extra_attributes.*.key.distinct'  => 'Tên thông tin bổ sung không được trùng nhau.',
            'extra_attributes.*.value.max'     => 'Nội dung thông tin không được vượt quá 1000 ký tự.',
        ];
    }
}
