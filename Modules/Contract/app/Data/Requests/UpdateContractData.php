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

class UpdateContractData extends Data
{
    public function __construct(
        #[Required]
        public readonly string $vendor_id,

        #[Required]
        public readonly string $contract_type_id,

        #[Required, StringType, Max(100)]
        public readonly string $contract_number,

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
        $currentId = request()->route('contract')?->id;

        return [
            'vendor_id'              => ['required', Rule::exists('vendors', 'id')],
            'contract_type_id'       => ['required', Rule::exists('contract_types', 'id')],
            'contract_number'        => [
                'required', 'string', 'max:100',
                Rule::unique('contracts', 'contract_number')->ignore($currentId),
            ],
            'renewal_period_months'  => ['nullable', 'integer', 'min:1', 'max:120', 'required_if:is_auto_renew,1'],
            'status'                 => ['required', Rule::enum(ContractStatus::class)],
        ];
    }

    public static function messages(): array
    {
        return StoreContractData::messages();
    }
}
