<?php

namespace Modules\Employee\Data\Requests;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\File;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Mimes;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;

class UpdateEmployeeData extends Data
{
    public function __construct(
        #[Required, StringType, Max(150)]
        public readonly string $full_name,

        #[Nullable, Email, Max(150)]
        public readonly ?string $email,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $phone,

        #[Nullable, Url, Max(255)]
        public readonly ?string $facebook_url,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $job_title,

        public readonly array $department_ids = [],

        #[Nullable, File, Mimes(['jpg', 'jpeg', 'png', 'webp']), Max(5120)]
        public readonly ?UploadedFile $avatar = null,
    ) {}

    public static function rules(): array
    {
        return StoreEmployeeData::rules();
    }

    public static function messages(): array
    {
        return StoreEmployeeData::messages();
    }
}
