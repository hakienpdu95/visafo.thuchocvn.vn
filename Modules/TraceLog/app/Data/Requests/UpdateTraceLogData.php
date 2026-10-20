<?php

namespace Modules\TraceLog\Data\Requests;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class UpdateTraceLogData extends Data
{
    public function __construct(
        public readonly string $exp_date,
        public readonly ?string $mfg_date = null,
        public readonly ?string $batch_code = null,
        public readonly ?string $product_batch_id = null,
        public readonly ?string $vendor_id = null,
        public readonly ?string $partner_product_id = null,
        public readonly ?string $supplier_name = null,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'trace_code'         => ['prohibited'],
            'mfg_date'           => ['nullable', 'date'],
            'exp_date'           => array_filter([
                'required', 'date',
                filled($context->payload['mfg_date'] ?? null) ? 'after_or_equal:mfg_date' : null,
            ]),
            'batch_code'         => ['nullable', 'string', 'max:100'],
            'product_batch_id'   => ['nullable', 'string', Rule::exists('product_batches', 'id')->whereNull('deleted_at')],
            'vendor_id'          => ['nullable', 'string', Rule::exists('vendors', 'id')->whereNull('deleted_at')],
            'partner_product_id' => ['nullable', 'string', Rule::exists('partner_products', 'id')->whereNull('deleted_at')],
            'supplier_name'      => ['nullable', 'string', 'max:255'],
        ];
    }

    public static function messages(): array
    {
        return [
            'trace_code.prohibited'     => 'Không được sửa mã TXNG / QR của tem.',
            'mfg_date.date'             => 'NSX không hợp lệ.',
            'exp_date.required'         => 'HSD là bắt buộc.',
            'exp_date.date'             => 'HSD không hợp lệ.',
            'exp_date.after_or_equal'   => 'HSD phải sau hoặc bằng NSX.',
            'batch_code.max'            => 'Mã lô không được vượt quá 100 ký tự.',
            'product_batch_id.exists'   => 'Lô nhập kho không hợp lệ.',
            'vendor_id.exists'          => 'Nhà cung cấp không hợp lệ.',
            'partner_product_id.exists' => 'Hàng hóa nhà cung cấp không hợp lệ.',
            'supplier_name.max'         => 'Nguồn cung không được vượt quá 255 ký tự.',
        ];
    }
}
