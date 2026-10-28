<?php

namespace Modules\Compliance\Enums;

enum TraceVisibility: string
{
    case Full   = 'full';
    case Status = 'status';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Full   => 'Công khai cả file',
            self::Status => 'Chỉ hiện trạng thái "Đã đạt kiểm định"',
            self::Hidden => 'Không hiển thị',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Full   => 'badge-info',
            self::Status => 'badge-success',
            self::Hidden => 'badge-ghost',
        };
    }
}
