<?php

namespace Modules\Contract\Enums;

enum ContractStatus: string
{
    case Active     = 'active';
    case Expired    = 'expired';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::Active     => 'Đang hiệu lực',
            self::Expired    => 'Đã hết hạn',
            self::Terminated => 'Đã chấm dứt',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active     => 'badge-success',
            self::Expired    => 'badge-warning',
            self::Terminated => 'badge-neutral',
        };
    }
}
