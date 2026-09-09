<?php

namespace Modules\Product\Data\Requests;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreBrandData extends Data
{
    public function __construct(
        #[Required, StringType, Max(150)]
        public readonly string $name,

        #[Nullable, StringType, Max(1000)]
        public readonly ?string $description,
    ) {}

    public static function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('brands', 'name'),
            ],
        ];
    }

    public static function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên thương hiệu.',
            'name.string'   => 'Tên thương hiệu không hợp lệ.',
            'name.max'      => 'Tên thương hiệu không được vượt quá 150 ký tự.',
            'name.unique'   => 'Thương hiệu này đã tồn tại.',

            'description.string' => 'Mô tả không hợp lệ.',
            'description.max'    => 'Mô tả không được vượt quá 1000 ký tự.',
        ];
    }
}
