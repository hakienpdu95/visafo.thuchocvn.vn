<?php

namespace Modules\Warehouse\Data\Requests;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Validation\Rule;
use Modules\Warehouse\Enums\InboundReceiptStatus;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreInboundReceiptData extends Data
{
    public function __construct(
        #[Required]
        public readonly string $vendor_id,

        #[Required, StringType, Max(100)]
        public readonly string $receipt_number,

        #[Required, Date]
        public readonly string $received_date,

        public readonly InboundReceiptStatus $status = InboundReceiptStatus::Draft,

        #[Nullable, StringType]
        public readonly ?string $notes,
    ) {}

    public static function rules(): array
    {
        return [
            'vendor_id' => ['required', Rule::exists('vendors', 'id')->where('organization_id', TenantContext::getOrganizationId())],
            'receipt_number' => [
                'required', 'string', 'max:100',
                Rule::unique('inbound_receipts', 'receipt_number')->where('organization_id', TenantContext::getOrganizationId()),
            ],
        ];
    }

    public static function messages(): array
    {
        return [
            'vendor_id.required' => 'Vui lòng chọn nhà cung cấp.',
            'vendor_id.exists'   => 'Nhà cung cấp không hợp lệ.',

            'receipt_number.required' => 'Vui lòng nhập mã phiếu nhập.',
            'receipt_number.string'   => 'Mã phiếu nhập không hợp lệ.',
            'receipt_number.max'      => 'Mã phiếu nhập không được vượt quá 100 ký tự.',
            'receipt_number.unique'   => 'Mã phiếu nhập này đã tồn tại.',

            'received_date.required' => 'Vui lòng chọn ngày nhận hàng.',
            'received_date.date'     => 'Ngày nhận hàng không hợp lệ.',

            'status.enum'       => 'Trạng thái không hợp lệ.',
            'status.prohibited' => 'Không thể chuyển trực tiếp sang "Đã hoàn tất" ở đây — dùng nút "Hoàn tất & Sinh tem QR" ở trang chi tiết phiếu nhập.',
        ];
    }
}
