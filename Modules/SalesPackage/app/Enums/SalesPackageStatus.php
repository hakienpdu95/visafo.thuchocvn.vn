<?php

namespace Modules\SalesPackage\Enums;

enum SalesPackageStatus: string
{
    case Draft     = 'draft';
    case Finalized = 'finalized';
    case Sent      = 'sent';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Nháp',
            self::Finalized => 'Đã chốt',
            self::Sent      => 'Đã gửi đối tác',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft     => 'badge-neutral',
            self::Finalized => 'badge-info',
            self::Sent      => 'badge-success',
        };
    }

    /**
     * @return self[]
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Draft     => [self::Finalized],
            self::Finalized => [self::Sent, self::Draft],
            self::Sent      => [],
        };
    }
}
