<?php

namespace Modules\Warehouse\Enums;

enum OutboundOrderStatus: string
{
    case Draft     = 'draft';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Nháp — đang chọn lô',
            self::Completed => 'Đã xuất kho',
            self::Cancelled => 'Đã hủy',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft     => 'badge-neutral',
            self::Completed => 'badge-success',
            self::Cancelled => 'badge-error',
        };
    }
}
