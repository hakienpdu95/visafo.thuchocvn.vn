<?php

namespace Modules\FoodInspection\Support;

/** Gợi ý khối lượng/thể tích mẫu tối thiểu theo quy định: 100g cho món đặc, 150ml cho món lỏng. */
class SampleVolume
{
    private const LIQUID_KEYWORDS = ['canh', 'súp', 'soup', 'cháo', 'nước', 'sữa', 'sinh tố', 'chè', 'nước ép'];

    public static function suggest(string $dishName): string
    {
        $name = mb_strtolower($dishName);

        // Sữa chua là thực phẩm đặc/sệt.
        if (str_contains($name, 'sữa chua')) {
            return '100g';
        }

        foreach (self::LIQUID_KEYWORDS as $keyword) {
            if (str_contains($name, $keyword)) {
                return '150ml';
            }
        }

        return '100g';
    }
}
