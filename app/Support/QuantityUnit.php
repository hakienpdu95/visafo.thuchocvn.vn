<?php

namespace App\Support;

class QuantityUnit
{
    public const DECIMALS = 2;

    private const DECIMAL_UNITS = ['kg', 'g', 'gr', 'gam', 'gram', 'lạng', 'yến', 'tạ', 'tấn', 'l', 'lít', 'lit', 'ml', 'm', 'mét', 'm2', 'm3', 'khối'];

    public static function allowsDecimal(?string $unit): bool
    {
        $tokens = preg_split('/[\s\/,.\-]+/u', mb_strtolower(trim((string) $unit)), -1, PREG_SPLIT_NO_EMPTY);

        return array_intersect($tokens, self::DECIMAL_UNITS) !== [];
    }
}
