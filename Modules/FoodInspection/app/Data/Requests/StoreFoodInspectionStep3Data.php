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

class StoreFoodInspectionStep3Data extends Data
{
    /** @param  array<int, Step3DetailData>  $details */
    public function __construct(
        #[Required, StringType, Exists('customers', 'id')]
        public readonly string $customer_id,

        #[Required, Date]
        public readonly string $inspection_date,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $location_name = null,

        #[Nullable, StringType, Max(2000)]
        public readonly ?string $note = null,

        #[Required, ArrayType, Min(1), DataCollectionOf(Step3DetailData::class)]
        public readonly array $details = [],
    ) {}

    public static function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            foreach ((array) ($v->getData()['details'] ?? []) as $i => $row) {
                $portion = $row['portion_time'] ?? null;
                $eat = $row['eat_time'] ?? null;

                // Giờ bắt đầu ăn không được trước giờ chia xong ("H:i" so sánh chuỗi được vì cùng độ dài, zero-padded).
                if ($portion && $eat && $eat < $portion) {
                    $v->errors()->add("details.$i.eat_time", 'Giờ bắt đầu ăn không được trước giờ chia món ăn xong.');
                }

                $failed = array_key_exists('sensory_eval', $row) && in_array($row['sensory_eval'], ['0', 0, false, 'false'], true);
                if ($failed && trim((string) ($row['action_taken'] ?? '')) === '') {
                    $v->errors()->add("details.$i.action_taken", 'Bắt buộc ghi biện pháp xử lý khi cảm quan không đạt.');
                }
            }
        });
    }

    public static function messages(): array
    {
        return [
            'customer_id.required'     => 'Vui lòng chọn khách hàng / điểm phục vụ.',
            'customer_id.exists'       => 'Khách hàng được chọn không hợp lệ.',
            'inspection_date.required' => 'Vui lòng chọn ngày kiểm tra.',
            'inspection_date.date'     => 'Ngày kiểm tra không đúng định dạng ngày.',
            'location_name.max'        => 'Địa điểm không được vượt quá :max ký tự.',
            'details.required'         => 'Cần ít nhất một món ăn để kiểm thực.',
            'details.min'              => 'Cần ít nhất một món ăn để kiểm thực.',
            'details.*.dish_name.required' => 'Vui lòng nhập tên món ăn ở mọi dòng.',
            'details.*.meal_time.required' => 'Vui lòng chọn ca/bữa ăn ở mọi dòng.',
            'details.*.portion_time.date_format' => 'Giờ chia xong phải có dạng giờ:phút.',
            'details.*.eat_time.date_format'     => 'Giờ bắt đầu ăn phải có dạng giờ:phút.',
            'details.*.equipment_used.max'       => 'Dụng cụ chứa đựng không được vượt quá :max ký tự.',
            'details.*.id.exists'      => 'Dòng món ăn được sửa không tồn tại.',
        ];
    }
}
