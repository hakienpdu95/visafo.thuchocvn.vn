<?php

namespace Modules\Contract\Data\Requests;

use Illuminate\Validation\Rule;
use Modules\Contract\Enums\ContractStatus;
use Spatie\LaravelData\Attributes\Validation\AfterOrEqual;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreContractData extends Data
{
    public function __construct(
        #[Required]
        public readonly string $vendor_id,

        #[Required]
        public readonly string $contract_type_id,

        #[Required, StringType, Max(255)]
        public readonly string $name,

        #[Nullable, Numeric, Min(0)]
        public readonly ?float $total_value,

        #[Required, Date]
        public readonly string $start_date,

        #[Nullable, Date, AfterOrEqual('start_date')]
        public readonly ?string $end_date,

        public readonly bool $is_auto_renew = false,

        #[Nullable]
        public readonly ?int $renewal_period_months = null,

        public readonly ContractStatus $status = ContractStatus::Active,
    ) {}

    public static function rules(): array
    {
        return [
            'vendor_id'              => ['required', Rule::exists('vendors', 'id')],
            'contract_type_id'       => ['required', Rule::exists('contract_types', 'id')],
            'renewal_period_months'  => ['nullable', 'integer', 'min:1', 'max:120', 'required_if:is_auto_renew,1'],
            'status'                 => ['required', Rule::enum(ContractStatus::class)],
        ];
    }

    public static function messages(): array
    {
        return [
            'vendor_id.required' => 'Vui lòng chọn nhà cung cấp.',
            'vendor_id.exists'   => 'Nhà cung cấp không hợp lệ.',

            'contract_type_id.required' => 'Vui lòng chọn loại hợp đồng.',
            'contract_type_id.exists'   => 'Loại hợp đồng không hợp lệ.',

            'name.required' => 'Vui lòng nhập tên hợp đồng.',
            'name.string'   => 'Tên hợp đồng không hợp lệ.',
            'name.max'      => 'Tên hợp đồng không được vượt quá 255 ký tự.',

            'total_value.numeric' => 'Giá trị hợp đồng không hợp lệ.',
            'total_value.min'     => 'Giá trị hợp đồng không được âm.',

            'start_date.required' => 'Vui lòng chọn ngày bắt đầu.',
            'start_date.date'     => 'Ngày bắt đầu không hợp lệ.',

            'end_date.date'           => 'Ngày kết thúc không hợp lệ.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',

            'renewal_period_months.integer'      => 'Chu kỳ gia hạn không hợp lệ.',
            'renewal_period_months.min'           => 'Chu kỳ gia hạn tối thiểu 1 tháng.',
            'renewal_period_months.max'           => 'Chu kỳ gia hạn tối đa 120 tháng.',
            'renewal_period_months.required_if'   => 'Vui lòng nhập chu kỳ gia hạn khi bật tự động gia hạn.',

            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.enum'     => 'Trạng thái không hợp lệ.',
        ];
    }
}
