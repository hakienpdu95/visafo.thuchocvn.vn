<?php

namespace Modules\FoodInspection\Data\Requests;

use Illuminate\Contracts\Validation\Validator;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreFoodInspectionStep1Data extends Data
{
    /**
     * @param  array<int, Step1DetailData>  $details
     * @param  array<int, \Illuminate\Http\UploadedFile>  $attachments
     * @param  array<int, string>  $remove_attachments
     */
    public function __construct(
        #[Required, Date]
        public readonly string $inspected_at,

        // Khách hàng / điểm phục vụ: gắn chặt vào sổ để truy xuất xuyên suốt sang Bước 2, Bước 3.
        #[Required, StringType, Exists('customers', 'id')]
        public readonly string $customer_id,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $inspection_location = null,

        #[Nullable, StringType, Max(2000)]
        public readonly ?string $note = null,

        #[Required, ArrayType, Min(1), DataCollectionOf(Step1DetailData::class)]
        public readonly array $details = [],

        #[Nullable, ArrayType, Max(10)]
        public readonly array $attachments = [],

        /** Chỉ dùng khi sửa: đường dẫn các chứng từ đã lưu cần gỡ khỏi sổ. */
        #[Nullable, ArrayType]
        public readonly array $remove_attachments = [],
    ) {}

    public static function rules(): array
    {
        return [
            'attachments.*'        => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'remove_attachments.*' => ['string', 'max:255'],
        ];
    }

    public static function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            foreach ((array) $v->getData()['details'] ?? [] as $i => $row) {
                $failed = ($row['sensory_result'] ?? null) === 'fail' || ($row['quick_test_result'] ?? null) === 'fail';
                if ($failed && trim((string) ($row['handling_measure'] ?? '')) === '') {
                    $v->errors()->add("details.$i.handling_measure", 'Bắt buộc ghi biện pháp xử lý khi cảm quan hoặc test nhanh không đạt.');
                }
            }
        });
    }

    public static function messages(): array
    {
        return [
            'inspected_at.required'   => 'Vui lòng chọn thời gian kiểm tra.',
            'inspected_at.date'       => 'Thời gian kiểm tra không hợp lệ.',
            'customer_id.required'    => 'Vui lòng chọn khách hàng / điểm phục vụ.',
            'customer_id.exists'      => 'Khách hàng được chọn không hợp lệ.',
            'details.required'        => 'Cần ít nhất một dòng thực phẩm để kiểm thực.',
            'details.min'             => 'Cần ít nhất một dòng thực phẩm để kiểm thực.',
            'details.*.product_name.required'   => 'Vui lòng nhập tên thực phẩm ở mọi dòng.',
            'details.*.vendor_name.required'    => 'Vui lòng nhập tên cơ sở cung cấp ở mọi dòng.',
            'details.*.deliverer_name.required' => 'Vui lòng nhập tên người giao hàng ở mọi dòng.',
            'details.*.received_at.required'    => 'Vui lòng nhập thời gian nhập hàng ở mọi dòng.',
            'details.*.received_at.date'        => 'Thời gian nhập hàng không hợp lệ.',
            'details.*.id.exists'     => 'Dòng hàng được sửa không tồn tại.',
            'attachments.max'         => 'Chỉ được đính kèm tối đa :max chứng từ.',
            'attachments.*.mimes'     => 'Chứng từ chỉ nhận ảnh (jpg, png, webp) hoặc PDF.',
            'attachments.*.max'       => 'Mỗi chứng từ không được vượt quá 5MB.',
        ];
    }
}
