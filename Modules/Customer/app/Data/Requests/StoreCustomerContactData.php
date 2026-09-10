<?php

namespace Modules\Customer\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreCustomerContactData extends Data
{
    public function __construct(
        #[Required, StringType, Max(100)]
        public readonly string $name,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $title,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $phone,

        #[Nullable, Email, Max(100)]
        public readonly ?string $email,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $note,
    ) {}

    public static function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập họ và tên.',
            'name.string'   => 'Họ và tên không hợp lệ.',
            'name.max'      => 'Họ và tên không được vượt quá 100 ký tự.',

            'title.string' => 'Chức vụ không hợp lệ.',
            'title.max'    => 'Chức vụ không được vượt quá 100 ký tự.',

            'phone.string' => 'Số điện thoại không hợp lệ.',
            'phone.max'    => 'Số điện thoại không được vượt quá 20 ký tự.',

            'email.string' => 'Email không hợp lệ.',
            'email.email'  => 'Email không đúng định dạng.',
            'email.max'    => 'Email không được vượt quá 100 ký tự.',

            'note.string' => 'Ghi chú không hợp lệ.',
            'note.max'    => 'Ghi chú không được vượt quá 255 ký tự.',
        ];
    }
}
