<?php

namespace Modules\Product\Data\Requests;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateCategoryData extends Data
{
    public function __construct(
        #[Required, StringType, Max(60), Regex('/^[a-z0-9_]+$/')]
        public readonly string $code,

        #[Required, StringType, Max(255)]
        public readonly string $name,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $description,

        public readonly bool $is_active,
    ) {}

    public static function rules(): array
    {
        $currentId = request()->route('category')?->id;

        return [
            'code' => [
                'required', 'string', 'max:60', 'regex:/^[a-z0-9_]+$/',
                Rule::unique('categories', 'code')->ignore($currentId),
            ],
        ];
    }

    public static function messages(): array
    {
        return [
            'code.required' => 'Vui lòng nhập mã nhóm hàng.',
            'code.string'   => 'Mã nhóm hàng không hợp lệ.',
            'code.max'      => 'Mã nhóm hàng không được vượt quá 60 ký tự.',
            'code.regex'    => 'Mã nhóm hàng chỉ được chứa chữ thường, số và dấu gạch dưới.',
            'code.unique'   => 'Mã nhóm hàng này đã tồn tại.',

            'name.required' => 'Vui lòng nhập tên nhóm hàng.',
            'name.string'   => 'Tên nhóm hàng không hợp lệ.',
            'name.max'      => 'Tên nhóm hàng không được vượt quá 255 ký tự.',

            'description.string' => 'Mô tả không hợp lệ.',
            'description.max'    => 'Mô tả không được vượt quá 255 ký tự.',
        ];
    }
}
