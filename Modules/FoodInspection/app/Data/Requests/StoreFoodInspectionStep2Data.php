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

class StoreFoodInspectionStep2Data extends Data
{
    /** @param  array<int, Step2DetailData>  $details */
    public function __construct(
        #[Required, StringType, Exists('customers', 'id')]
        public readonly string $customer_id,

        #[Required, Date]
        public readonly string $inspection_date,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $location_name = null,

        #[Nullable, StringType, Max(2000)]
        public readonly ?string $note = null,

        #[Required, ArrayType, Min(1), DataCollectionOf(Step2DetailData::class)]
        public readonly array $details = [],
    ) {}

    public static function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            foreach ((array) ($v->getData()['details'] ?? []) as $i => $row) {
                $failed = collect(['hygiene_personnel', 'hygiene_equipment', 'hygiene_area', 'sensory_eval'])
                    ->contains(fn (string $k) => array_key_exists($k, $row) && in_array($row[$k], ['0', 0, false, 'false'], true));

                if ($failed && trim((string) ($row['action_taken'] ?? '')) === '') {
                    $v->errors()->add("details.$i.action_taken", 'Bắt buộc ghi biện pháp xử lý khi có tiêu chí vệ sinh hoặc cảm quan không đạt.');
                }
            }
        });
    }

    public static function messages(): array
    {
        return [
            'customer_id.required'   => 'Vui lòng chọn cơ sở / doanh nghiệp suất ăn.',
            'customer_id.exists'     => 'Cơ sở được chọn không hợp lệ.',
            'inspection_date.required' => 'Vui lòng chọn ngày kiểm tra.',
            'inspection_date.date'   => 'Ngày kiểm tra không đúng định dạng ngày.',
            'location_name.max'      => 'Địa điểm không được vượt quá :max ký tự.',
            'details.required'       => 'Cần ít nhất một món ăn để kiểm thực.',
            'details.min'            => 'Cần ít nhất một món ăn để kiểm thực.',
            'details.*.dish_name.required' => 'Vui lòng nhập tên món ăn ở mọi dòng.',
            'details.*.meal_time.required' => 'Vui lòng chọn ca/bữa ăn ở mọi dòng.',
            'details.*.prep_time.date_format' => 'Thời gian sơ chế phải có dạng giờ:phút.',
            'details.*.cook_time.date_format' => 'Thời gian chế biến phải có dạng giờ:phút.',
            'details.*.id.exists'    => 'Dòng món ăn được sửa không tồn tại.',
            'details.*.menu_dish_id.exists' => 'Món ăn trong thực đơn không tồn tại.',
        ];
    }
}
