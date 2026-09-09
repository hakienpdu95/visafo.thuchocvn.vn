<?php

namespace Modules\Product\Enums;

enum DocumentGroupType: string
{
    case LegalFacility  = 'legal_facility';
    case Personnel      = 'personnel';
    case Traceability   = 'traceability';
    case MonitoringLogs = 'monitoring_logs';

    public function label(): string
    {
        return match ($this) {
            self::LegalFacility  => 'Hồ sơ pháp lý đối với cơ sở',
            self::Personnel      => 'Hồ sơ đối với nhân viên',
            self::Traceability   => 'Hồ sơ kiểm soát nguồn gốc nguyên liệu',
            self::MonitoringLogs => 'Sổ sách ghi chép và giám sát tại chỗ',
        };
    }
}
