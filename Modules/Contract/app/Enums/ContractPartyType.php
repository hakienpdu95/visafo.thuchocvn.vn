<?php

namespace Modules\Contract\Enums;

enum ContractPartyType: string
{
    case Input  = 'input';
    case Output = 'output';

    public function label(): string
    {
        return match ($this) {
            self::Input  => 'Đầu vào (Nhà cung cấp)',
            self::Output => 'Đầu ra (Khách hàng)',
        };
    }
}
