<?php

namespace Modules\Employee\Enums;

enum HealthRecordType: string
{
    case HealthCheck  = 'health_check';
    case AttpTraining = 'attp_training';

    public function label(): string
    {
        return match ($this) {
            self::HealthCheck  => 'Giấy khám sức khỏe',
            self::AttpTraining => 'Giấy xác nhận tập huấn kiến thức ATTP',
        };
    }

    public function defaultValidityMonths(): int
    {
        return match ($this) {
            self::HealthCheck  => 12,
            self::AttpTraining => 36,
        };
    }
}
