<?php

namespace Modules\Recall\Enums;

enum RecallSeverity: string
{
    case Minor    = 'minor';
    case Major    = 'major';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Minor    => 'Nhẹ',
            self::Major    => 'Nghiêm trọng',
            self::Critical => 'Rất nghiêm trọng — ảnh hưởng sức khỏe/tính mạng',
        };
    }
}
