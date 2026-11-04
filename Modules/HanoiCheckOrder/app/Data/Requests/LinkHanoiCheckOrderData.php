<?php

namespace Modules\HanoiCheckOrder\Data\Requests;

use Spatie\LaravelData\Data;

class LinkHanoiCheckOrderData extends Data
{
    public function __construct(
        public readonly ?string $hanoicheck_order_code = null,
        public readonly ?string $note = null,
    ) {}

    public static function rules(): array
    {
        return [
            'hanoicheck_order_code' => ['nullable', 'string', 'max:255', 'regex:/^\s*[A-Za-z0-9_.-]+\s*$/'],
            'note'                  => ['nullable', 'string', 'max:500'],
        ];
    }

    public static function messages(): array
    {
        return [
            'hanoicheck_order_code.max'   => 'Mã đơn HanoiCheck tối đa :max ký tự.',
            'hanoicheck_order_code.regex' => 'Mã đơn HanoiCheck chỉ gồm chữ không dấu, số và các ký tự _ . - (không có khoảng trắng).',
            'note.max'                    => 'Ghi chú tối đa :max ký tự.',
        ];
    }
}
