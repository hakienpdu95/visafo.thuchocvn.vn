<?php

namespace Modules\Organization\Enums;

enum OrganizationVersionStatus: string
{
    case Draft      = 'draft';
    case Active     = 'active';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Draft      => 'Bản nháp',
            self::Active     => 'Đang áp dụng',
            self::Superseded => 'Đã thay thế',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft      => 'badge-ghost',
            self::Active     => 'badge-success',
            self::Superseded => 'badge-neutral',
        };
    }
}
