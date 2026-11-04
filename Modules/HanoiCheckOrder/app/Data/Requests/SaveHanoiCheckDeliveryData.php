<?php

namespace Modules\HanoiCheckOrder\Data\Requests;

use Spatie\LaravelData\Data;

class SaveHanoiCheckDeliveryData extends Data
{
    public function __construct(
        public readonly ?string $transporter_id = null,
        public readonly array $photos = [],
        public readonly array $remove_photo_ids = [],
    ) {}

    public static function rules(): array
    {
        return [
            'transporter_id'     => ['nullable', 'string', 'exists:hanoicheck_transporters,id'],
            'photos'             => ['array', 'max:3'],
            'photos.*'           => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'remove_photo_ids'   => ['array'],
            'remove_photo_ids.*' => ['string'],
        ];
    }

    public static function messages(): array
    {
        return [
            'transporter_id.exists' => 'Người giao hàng không tồn tại.',
            'photos.max'            => 'Tối đa :max ảnh tổng quan.',
            'photos.*.mimes'        => 'Ảnh phải là JPG, PNG hoặc WEBP (HanoiCheck không nhận định dạng khác).',
            'photos.*.max'          => 'Mỗi ảnh tối đa 10MB.',
        ];
    }
}
