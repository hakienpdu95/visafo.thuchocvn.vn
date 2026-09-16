<?php

namespace Modules\Product\Enums;

enum InternalTabGroup: string
{
    case Legal     = 'legal';
    case Operation = 'operation';
    case Hr        = 'hr';

    public function label(): string
    {
        return match ($this) {
            self::Legal     => 'Pháp lý & năng lực',
            self::Operation => 'ATTP & vận hành',
            self::Hr        => 'Nhân sự',
        };
    }
}
