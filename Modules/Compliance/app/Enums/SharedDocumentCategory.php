<?php

namespace Modules\Compliance\Enums;

enum SharedDocumentCategory: string
{
    case Policy   = 'policy';
    case Template = 'template';
    case Training = 'training';
    case Other    = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Policy   => 'Quy chế & Chính sách',
            self::Template => 'Biểu mẫu chuẩn',
            self::Training => 'Tài liệu đào tạo',
            self::Other    => 'Khác',
        };
    }
}
