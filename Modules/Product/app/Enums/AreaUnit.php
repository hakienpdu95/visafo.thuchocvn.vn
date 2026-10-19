<?php

namespace Modules\Product\Enums;

enum AreaUnit: string
{
    case SaoBacBo = 'sao_bac_bo';
    case SaoTrungBo = 'sao_trung_bo';
    case SquareMeter = 'm2';
    case Hectare = 'ha';

    public function label(): string
    {
        return match ($this) {
            self::SaoBacBo    => 'Sào (Bắc Bộ - 360m²)',
            self::SaoTrungBo  => 'Sào (Trung Bộ - 500m²)',
            self::SquareMeter => 'm²',
            self::Hectare     => 'ha',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::SaoBacBo, self::SaoTrungBo => 'sào',
            self::SquareMeter                => 'm²',
            self::Hectare                    => 'ha',
        };
    }

    public function squareMeters(): int
    {
        return match ($this) {
            self::SaoBacBo    => 360,
            self::SaoTrungBo  => 500,
            self::SquareMeter => 1,
            self::Hectare     => 10000,
        };
    }

    public function toHectare(float $value): float
    {
        return round($value * $this->squareMeters() / 10000, 4);
    }
}
