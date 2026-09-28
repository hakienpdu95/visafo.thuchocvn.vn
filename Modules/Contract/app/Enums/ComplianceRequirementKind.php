<?php

namespace Modules\Contract\Enums;

enum ComplianceRequirementKind: string
{
    case Document = 'document';
    case Contract = 'contract';

    public function label(): string
    {
        return match ($this) {
            self::Document => 'Hồ sơ pháp lý',
            self::Contract => 'Hợp đồng',
        };
    }
}
