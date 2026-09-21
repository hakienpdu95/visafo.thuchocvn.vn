<?php

namespace Modules\FoodInspection\Enums;

enum SampleStatus: string
{
    case Stored    = 'stored';
    case Destroyed = 'destroyed';

    public function label(): string
    {
        return match ($this) {
            self::Stored    => 'Lưu mẫu',
            self::Destroyed => 'Đã hủy',
        };
    }
}
