<?php

namespace Modules\Product\Data\Requests;

use Illuminate\Validation\Rule;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Enums\ProductType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateProductData extends Data
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

        public readonly ProductStatus $status,
    ) {}

    public static function rules(): array
    {
        $currentId = request()->route('product')?->id;

        return [
            'sku' => [
                'required', 'string', 'max:100',
                Rule::unique('products', 'sku')
                    ->ignore($currentId),
            ],
            'category_id'  => ['required', Rule::exists('categories', 'id')],
            'product_type' => ['required', Rule::enum(ProductType::class)],
        ];
    }

    public static function messages(): array
    {
        return StoreProductData::messages();
    }
}
