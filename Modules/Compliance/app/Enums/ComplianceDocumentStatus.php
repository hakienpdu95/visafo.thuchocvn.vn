<?php

namespace Modules\Compliance\Enums;

enum ComplianceDocumentStatus: string
{
    case Pending    = 'pending';
    case Active     = 'active';
    case Expired    = 'expired';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Pending    => 'Chờ duyệt',
            self::Active     => 'Đang hiệu lực',
            self::Expired    => 'Hết hạn',
            self::Superseded => 'Đã thay thế',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending    => 'badge-neutral',
            self::Active     => 'badge-success',
            self::Expired    => 'badge-error',
            self::Superseded => 'badge-ghost',
        };
    }
}
