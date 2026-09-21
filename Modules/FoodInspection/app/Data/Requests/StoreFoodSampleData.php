<?php

namespace Modules\FoodInspection\Data\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Carbon;
use Modules\FoodInspection\Models\FoodSampleDetail;
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

class StoreFoodSampleData extends Data
{
    /** @param  array<int, SampleDetailData>  $details */
    public function __construct(
        #[Required, StringType, Exists('customers', 'id')]
        public readonly string $customer_id,

        #[Required, Date]
        public readonly string $sample_date,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $location_name = null,

        #[Nullable, StringType, Max(2000)]
        public readonly ?string $note = null,

        #[Required, ArrayType, Min(1), DataCollectionOf(SampleDetailData::class)]
        public readonly array $details = [],
    ) {}

    public static function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            foreach ((array) ($v->getData()['details'] ?? []) as $i => $row) {
                $destroyedAt = filled($row['destroyed_at'] ?? null) ? $row['destroyed_at'] : null;
                $destroyer = trim((string) ($row['destroyer_name'] ?? ''));

                if ($destroyedAt === null && $destroyer === '') {
                    continue;
                }
                if ($destroyedAt === null) {
                    $v->errors()->add("details.$i.destroyed_at", 'Vui lòng nhập thời gian hủy mẫu.');
                    continue;
                }
                if ($destroyer === '') {
                    $v->errors()->add("details.$i.destroyer_name", 'Vui lòng nhập người hủy mẫu.');
                }

                try {
                    $destroyed = Carbon::parse($destroyedAt);
                    $sampled = Carbon::parse($row['sampled_at'] ?? null);
                } catch (\Throwable) {
                    continue; // định dạng ngày sai đã có lỗi riêng
                }

                // Quy định: chỉ được hủy mẫu sau ít nhất 24 giờ kể từ lúc lấy mẫu.
                $earliest = $sampled->copy()->addHours(FoodSampleDetail::MIN_RETENTION_HOURS);
                if ($destroyed->lt($earliest)) {
                    $v->errors()->add("details.$i.destroyed_at", 'Chưa đủ 24 giờ lưu mẫu — chỉ được hủy từ ' . $earliest->format('d/m/Y H:i') . '.');
                } elseif ($destroyed->gt(now()->addMinutes(10))) {
                    $v->errors()->add("details.$i.destroyed_at", 'Thời gian hủy mẫu không được ở tương lai.');
                }
            }
        });
    }

    public static function messages(): array
    {
        return [
            'customer_id.required'   => 'Vui lòng chọn khách hàng / điểm phục vụ.',
            'customer_id.exists'     => 'Khách hàng được chọn không hợp lệ.',
            'sample_date.required'   => 'Vui lòng chọn ngày lưu mẫu.',
            'sample_date.date'       => 'Ngày lưu mẫu không đúng định dạng ngày.',
            'location_name.max'      => 'Địa điểm không được vượt quá :max ký tự.',
            'details.required'       => 'Cần ít nhất một mẫu thức ăn.',
            'details.min'            => 'Cần ít nhất một mẫu thức ăn.',
            'details.*.dish_name.required'    => 'Vui lòng nhập tên mẫu thức ăn ở mọi dòng.',
            'details.*.sample_volume.required' => 'Vui lòng nhập khối lượng/thể tích mẫu ở mọi dòng.',
            'details.*.sampled_at.required'   => 'Vui lòng nhập thời gian lấy mẫu ở mọi dòng.',
            'details.*.sampled_at.date'       => 'Thời gian lấy mẫu không đúng định dạng.',
            'details.*.sampler_name.required' => 'Vui lòng nhập người lấy mẫu ở mọi dòng.',
            'details.*.destroyed_at.date'     => 'Thời gian hủy mẫu không đúng định dạng.',
            'details.*.storage_temp.min'      => 'Nhiệt độ bảo quản không hợp lệ.',
            'details.*.storage_temp.max'      => 'Nhiệt độ bảo quản không hợp lệ.',
            'details.*.id.exists'             => 'Mẫu được sửa không tồn tại.',
        ];
    }
}
