<?php

namespace Modules\Product\Data\Requests;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\File;
use Spatie\LaravelData\Attributes\Validation\Image;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Mimes;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\RequiredIf;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreFarmingLogData extends Data
{
    public function __construct(
        #[Required, In('land_prep', 'planting', 'water', 'fertilizer', 'pesticide', 'harvest', 'other', 'cultivation')]
        public readonly string $activity_type,

        #[Required, Date]
        public readonly string $activity_date,

        #[Nullable, Exists('vendor_farming_steps', 'id')]
        public readonly ?string $vendor_farming_step_id,

        #[Nullable, RequiredIf('activity_type', 'fertilizer', 'pesticide'), StringType, Max(255)]
        public readonly ?string $material_name,

        #[Nullable, RequiredIf('activity_type', 'fertilizer', 'pesticide'), StringType, Max(100)]
        public readonly ?string $dosage,

        #[Nullable, IntegerType, Min(0), Max(365)]
        public readonly ?int $isolation_days,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $method_or_target,

        #[Nullable, RequiredIf('activity_type', 'harvest'), StringType, Max(100)]
        public readonly ?string $harvest_yield,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $buyer_name,

        #[Nullable, StringType, Max(150)]
        public readonly ?string $operator_name,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $notes,

        #[Nullable, File, Image, Mimes(['jpg', 'jpeg', 'png']), Max(10240)]
        public readonly ?UploadedFile $image,
    ) {}

    public static function messages(): array
    {
        return [
            'activity_type.required' => 'Vui lòng chọn loại hoạt động.',
            'activity_type.in'       => 'Loại hoạt động không hợp lệ.',

            'activity_date.required' => 'Vui lòng chọn ngày thực hiện.',
            'activity_date.date'     => 'Ngày thực hiện không hợp lệ.',

            'material_name.required_if' => 'Vui lòng nhập loại vật tư sử dụng.',
            'material_name.max'         => 'Tên vật tư không được vượt quá 255 ký tự.',

            'dosage.required_if' => 'Vui lòng nhập liều lượng sử dụng.',
            'dosage.max'         => 'Liều lượng không được vượt quá 100 ký tự.',

            'isolation_days.integer' => 'Thời gian cách ly phải là số nguyên (ngày).',
            'isolation_days.min'     => 'Thời gian cách ly không được âm.',
            'isolation_days.max'     => 'Thời gian cách ly không được vượt quá 365 ngày.',

            'harvest_yield.required_if' => 'Vui lòng nhập sản lượng thu hoạch.',
            'harvest_yield.max'         => 'Sản lượng không được vượt quá 100 ký tự.',

            'buyer_name.max'    => 'Tên cơ sở tiêu thụ không được vượt quá 255 ký tự.',
            'operator_name.max' => 'Tên người thực hiện không được vượt quá 150 ký tự.',
            'notes.max'         => 'Phương pháp thực hiện / ghi chú không được vượt quá 500 ký tự.',

            'image.image' => 'Ảnh minh chứng không hợp lệ.',
            'image.mimes' => 'Chỉ chấp nhận ảnh JPG hoặc PNG.',
            'image.max'   => 'Dung lượng ảnh không được vượt quá 10MB.',
        ];
    }
}
