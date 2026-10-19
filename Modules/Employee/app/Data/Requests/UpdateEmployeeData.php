<?php

namespace Modules\Employee\Data\Requests;

use Illuminate\Http\UploadedFile;
use Modules\Employee\Enums\Gender;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\File;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Mimes;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;

class UpdateEmployeeData extends Data
{
    public function __construct(
        #[Required, StringType, Max(150)]
        public readonly string $full_name,

        #[Nullable, Enum(Gender::class)]
        public readonly ?string $gender,

        #[Nullable, Date]
        public readonly ?string $dob,

        #[Nullable, Email, Max(150)]
        public readonly ?string $email,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $phone,

        #[Nullable, Url, Max(255)]
        public readonly ?string $facebook_url,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $job_title,

        #[Nullable, Date]
        public readonly ?string $start_date,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $address,

        #[Nullable, Regex('/^\d{12}$/')]
        public readonly ?string $id_card_number,

        #[Nullable, Date]
        public readonly ?string $id_card_issue_date,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $id_card_issue_place,

        public readonly array $department_ids = [],

        #[Nullable, File, Mimes(['jpg', 'jpeg', 'png', 'webp']), Max(5120)]
        public readonly ?UploadedFile $avatar = null,

        #[Nullable, File, Mimes(['jpg', 'jpeg', 'png', 'webp']), Max(10240)]
        public readonly ?UploadedFile $id_card_front = null,

        #[Nullable, File, Mimes(['jpg', 'jpeg', 'png', 'webp']), Max(10240)]
        public readonly ?UploadedFile $id_card_back = null,

        public readonly bool $remove_id_card_front = false,

        public readonly bool $remove_id_card_back = false,
    ) {}

    public function profileAttributes(): array
    {
        return [
            'full_name'           => $this->full_name,
            'gender'              => $this->gender,
            'dob'                 => $this->dob,
            'email'               => $this->email,
            'phone'               => $this->phone,
            'facebook_url'        => $this->facebook_url,
            'address'             => $this->address,
            'job_title'           => $this->job_title,
            'start_date'          => $this->start_date,
            'id_card_number'      => $this->id_card_number,
            'id_card_issue_date'  => $this->id_card_issue_date,
            'id_card_issue_place' => $this->id_card_issue_place,
        ];
    }

    public static function rules(): array
    {
        return StoreEmployeeData::rules();
    }

    public static function messages(): array
    {
        return StoreEmployeeData::messages();
    }
}
