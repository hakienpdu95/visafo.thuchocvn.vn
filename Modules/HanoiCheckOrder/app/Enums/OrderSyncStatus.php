<?php

namespace Modules\HanoiCheckOrder\Enums;

enum OrderSyncStatus: string
{
    case Idle = 'idle';
    case Queued = 'queued';
    case Waiting = 'waiting';
    case Synced = 'synced';
    case Incomplete = 'incomplete';
    case Failed = 'failed';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::Idle       => 'Chưa đồng bộ',
            self::Queued     => 'Đang chờ đồng bộ',
            self::Waiting    => 'Chờ dữ liệu nền',
            self::Synced     => 'Đã đồng bộ',
            self::Incomplete => 'Thiếu dữ liệu',
            self::Failed     => 'Lỗi gửi',
            self::Locked     => 'Đã chốt trên Sở',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Idle       => 'badge-ghost',
            self::Queued, self::Waiting => 'badge-info',
            self::Synced     => 'badge-success',
            self::Incomplete => 'badge-warning',
            self::Failed     => 'badge-error',
            self::Locked     => 'badge-neutral',
        };
    }
}
