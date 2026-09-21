<?php

namespace Modules\FoodInspection\Enums;

enum StorageCondition: string
{
    case Ambient = 'ambient';
    case Cold    = 'cold';

    public function label(): string
    {
        return match ($this) {
            self::Ambient => 'Nhiệt độ thường',
            self::Cold    => 'Lạnh',
        };
    }
}
