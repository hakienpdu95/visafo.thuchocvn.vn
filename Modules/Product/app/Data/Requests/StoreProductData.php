<?php

namespace Modules\Product\Data\Requests;

use Illuminate\Validation\Rule;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Enums\ProductType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreProductData extends Data
{
    public function __construct(
        #[Required, StringType, Max(100)]
        public readonly string $sku,

        #[Required, StringType, Max(255)]
        public readonly string $name,

        #[Required]
        public readonly string $category_id,

        public readonly ProductType $product_type,

        #[Required, StringType, Max(30)]
        public readonly string $unit,

        public readonly ProductStatus $status = ProductStatus::Active,
    ) {}

    public static function rules(): array
    {
        return [
            'sku' => [
                'required', 'string', 'max:100',
                Rule::unique('products', 'sku'),
            ],
            'category_id'  => ['required', Rule::exists('categories', 'id')],
            'product_type' => ['required', Rule::enum(ProductType::class)],
        ];
    }

    public static function messages(): array
    {
        return [
            'sku.required' => 'Vui lòng nhập mã SKU.',
            'sku.string'   => 'Mã SKU không hợp lệ.',
            'sku.max'      => 'Mã SKU không được vượt quá 100 ký tự.',
            'sku.unique'   => 'Mã SKU này đã tồn tại.',

            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'name.string'   => 'Tên sản phẩm không hợp lệ.',
            'name.max'      => 'Tên sản phẩm không được vượt quá 255 ký tự.',

            'category_id.required' => 'Vui lòng chọn nhóm thực phẩm.',
            'category_id.exists'   => 'Nhóm thực phẩm không hợp lệ.',

            'product_type.required' => 'Vui lòng chọn loại sản phẩm.',
            'product_type.enum'     => 'Loại sản phẩm không hợp lệ.',

            'unit.required' => 'Vui lòng nhập đơn vị tính.',
            'unit.string'   => 'Đơn vị tính không hợp lệ.',
            'unit.max'      => 'Đơn vị tính không được vượt quá 30 ký tự.',

            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.enum'     => 'Trạng thái không hợp lệ.',
        ];
    }
}
