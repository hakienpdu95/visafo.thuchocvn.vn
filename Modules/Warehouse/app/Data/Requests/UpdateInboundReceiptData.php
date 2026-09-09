<?php

namespace Modules\Warehouse\Data\Requests;

use Illuminate\Validation\Rule;
use Modules\Warehouse\Enums\InboundReceiptStatus;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateInboundReceiptData extends Data
{
    public function __construct(
        #[Required]
        public readonly string $vendor_id,

        #[Required, StringType, Max(100)]
        public readonly string $receipt_number,

        #[Required, Date]
        public readonly string $received_date,

        public readonly InboundReceiptStatus $status,

        #[Nullable, StringType]
        public readonly ?string $notes,
    ) {}

    public static function rules(): array
    {
        $inboundReceipt = request()->route('inbound_receipt');
        $currentId      = $inboundReceipt?->id;

        return [
            'vendor_id' => ['required', Rule::exists('vendors', 'id')],
            'receipt_number' => [
                'required', 'string', 'max:100',
                Rule::unique('inbound_receipts', 'receipt_number')
                    ->ignore($currentId),
            ],
            'status' => [
                'required',
                Rule::enum(InboundReceiptStatus::class),
                Rule::prohibitedIf(fn () => request('status') === InboundReceiptStatus::Completed->value
                    && $inboundReceipt?->status !== InboundReceiptStatus::Completed),
            ],
        ];
    }

    public static function messages(): array
    {
        return StoreInboundReceiptData::messages();
    }
}
