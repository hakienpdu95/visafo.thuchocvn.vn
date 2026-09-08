<?php

namespace Modules\Warehouse\Data\Requests;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\AfterOrEqual;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreBatchData extends Data
{
    public function __construct(
        #[Required]
        public readonly string $product_id,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $mfg_batch_number,

        #[Nullable, Date]
        public readonly ?string $mfg_date,

        #[Required, Date, AfterOrEqual('mfg_date')]
        public readonly string $exp_date,

        #[Required, Min(1)]
        public readonly int $initial_qty,
    ) {}

    public static function rules(): array
    {
        return [
            'product_id' => ['required', Rule::exists('products', 'id')->where('organization_id', TenantContext::getOrganizationId())],
        ];
    }

    public static function messages(): array
    {
        return [
            'product_id.required' => 'Vui lòng chọn sản phẩm.',
            'product_id.exists'   => 'Sản phẩm không hợp lệ.',

            'mfg_batch_number.string' => 'Mã lô nhà sản xuất không hợp lệ.',
            'mfg_batch_number.max'    => 'Mã lô nhà sản xuất không được vượt quá 100 ký tự.',

            'mfg_date.date' => 'Ngày sản xuất không hợp lệ.',

            'exp_date.required'      => 'Vui lòng nhập hạn sử dụng.',
            'exp_date.date'          => 'Hạn sử dụng không hợp lệ.',
            'exp_date.after_or_equal' => 'Hạn sử dụng phải sau hoặc bằng ngày sản xuất.',

            'initial_qty.required' => 'Vui lòng nhập số lượng.',
            'initial_qty.min'      => 'Số lượng phải lớn hơn 0.',
        ];
    }
}
