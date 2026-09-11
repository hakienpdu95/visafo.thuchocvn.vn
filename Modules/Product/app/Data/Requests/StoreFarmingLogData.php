<?php

namespace Modules\Product\Data\Requests;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\File;
use Spatie\LaravelData\Attributes\Validation\Image;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Mimes;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\RequiredIf;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreFarmingLogData extends Data
{
    public function __construct(
        #[Required, In('cultivation', 'water', 'fertilizer', 'pesticide', 'harvest', 'other')]
        public readonly string $activity_type,

        #[Required, Date]
        public readonly string $activity_date,

        #[Nullable, RequiredIf('activity_type', 'fertilizer'), Exists('agri_fertilizers', 'id')]
        public readonly ?string $agri_fertilizer_id,

        #[Nullable, RequiredIf('activity_type', 'pesticide'), Exists('agri_pesticides', 'id')]
        public readonly ?string $agri_pesticide_id,

        #[Nullable, Exists('vendor_farming_steps', 'id')]
        public readonly ?string $vendor_farming_step_id,

        #[Nullable, Numeric]
        public readonly ?string $quantity,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $unit,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $method_or_target,

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

            'agri_fertilizer_id.required_if' => 'Vui lòng chọn loại phân bón.',
            'agri_fertilizer_id.exists'      => 'Phân bón được chọn không hợp lệ.',

            'agri_pesticide_id.required_if' => 'Vui lòng chọn loại thuốc BVTV.',
            'agri_pesticide_id.exists'      => 'Thuốc BVTV được chọn không hợp lệ.',

            'quantity.numeric' => 'Số lượng không hợp lệ.',
            'unit.max'         => 'Đơn vị tính không được vượt quá 20 ký tự.',

            'image.image' => 'Ảnh minh chứng không hợp lệ.',
            'image.mimes' => 'Chỉ chấp nhận ảnh JPG hoặc PNG.',
            'image.max'   => 'Dung lượng ảnh không được vượt quá 10MB.',
        ];
    }
}
