<?php

namespace Modules\Employee\Enums;

enum Gender: string
{
    case Male   = 'nam';
    case Female = 'nu';
    case Other  = 'khac';

    public function label(): string
    {
        return match ($this) {
            self::Male   => 'Nam',
            self::Female => 'Nữ',
            self::Other  => 'Khác',
        };
    }
}
