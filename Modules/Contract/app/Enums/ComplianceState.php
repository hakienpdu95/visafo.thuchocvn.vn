<?php

namespace Modules\Contract\Enums;

enum ComplianceState: string
{
    case Ok       = 'ok';
    case Expiring = 'expiring';
    case Expired  = 'expired';
    case Missing  = 'missing';

    public function label(): string
    {
        return match ($this) {
            self::Ok       => 'Đã có',
            self::Expiring => 'Sắp hết hạn',
            self::Expired  => 'Đã hết hạn',
            self::Missing  => 'Còn thiếu',
        };
    }
}
