<?php

namespace Modules\Product\Data\Requests;

use Illuminate\Validation\Rule;
use Modules\Product\Enums\PartnerProductStatus;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StorePartnerProductData extends Data
{
    public function __construct(
        #[Required]
        public readonly string $vendor_id,

        #[Required]
        public readonly string $product_id,

        #[Required, StringType, Max(255)]
        public readonly string $name,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $manufacturer_name,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $origin_address,

        public readonly PartnerProductStatus $status = PartnerProductStatus::Active,
    ) {}

    public static function rules(): array
    {
        return [
            'vendor_id'  => ['required', Rule::exists('vendors', 'id')],
            'product_id' => ['required', Rule::exists('products', 'id')],
            'status'     => ['required', Rule::enum(PartnerProductStatus::class)],
        ];
    }

    public static function messages(): array
    {
        return [
            'vendor_id.required' => 'Vui lòng chọn nhà cung cấp.',
            'vendor_id.exists'   => 'Nhà cung cấp không hợp lệ.',

            'product_id.required' => 'Vui lòng ánh xạ với sản phẩm chuẩn của Visafo.',
            'product_id.exists'   => 'Sản phẩm chuẩn không hợp lệ.',

            'name.required' => 'Vui lòng nhập tên hàng do NCC kê khai.',
            'name.string'   => 'Tên hàng không hợp lệ.',
            'name.max'      => 'Tên hàng không được vượt quá 255 ký tự.',

            'manufacturer_name.string' => 'Tên nhà sản xuất không hợp lệ.',
            'manufacturer_name.max'    => 'Tên nhà sản xuất không được vượt quá 255 ký tự.',

            'origin_address.string' => 'Địa chỉ nguồn gốc không hợp lệ.',
            'origin_address.max'    => 'Địa chỉ nguồn gốc không được vượt quá 500 ký tự.',

            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.enum'     => 'Trạng thái không hợp lệ.',
        ];
    }
}
