<?php

namespace Modules\Product\Data\Requests;

use Illuminate\Validation\Rule;
use Modules\Product\Enums\ProductCategoryType;
use Modules\Product\Enums\ProductStatus;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateProductData extends Data
{
    public function __construct(
        #[Required, StringType, Max(100)]
        public readonly string $sku,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $barcode,

        #[Required, StringType, Max(255)]
        public readonly string $name,

        #[Nullable]
        public readonly ?string $brand_id,

        public readonly ProductCategoryType $category_type,

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
            'brand_id' => ['nullable', Rule::exists('brands', 'id')],
            'category_type' => ['required', Rule::enum(ProductCategoryType::class)],
        ];
    }

    public static function messages(): array
    {
        return StoreProductData::messages();
    }
}
