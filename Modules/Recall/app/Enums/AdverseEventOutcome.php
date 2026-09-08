<?php

namespace Modules\Recall\Enums;

enum AdverseEventOutcome: string
{
    case Recovered    = 'recovered';
    case Fatal        = 'fatal';
    case NotRecovered = 'not_recovered';
    case Unknown      = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Recovered    => 'Đã hồi phục',
            self::Fatal        => 'Tử vong',
            self::NotRecovered => 'Vẫn chưa hồi phục',
            self::Unknown      => 'Không biết',
        };
    }
}
