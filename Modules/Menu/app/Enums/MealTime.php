<?php

namespace Modules\Menu\Enums;

enum MealTime: string
{
    case Breakfast = 'breakfast';
    case Lunch     = 'lunch';
    case Afternoon = 'afternoon';
    case Dinner    = 'dinner';

    public function label(): string
    {
        return match ($this) {
            self::Breakfast => 'Bữa sáng',
            self::Lunch     => 'Bữa trưa',
            self::Afternoon => 'Bữa xế / phụ chiều',
            self::Dinner    => 'Bữa tối',
        };
    }

    /** @return array<int, array{value: string, text: string}> */
    public static function options(): array
    {
        return array_map(fn (self $m) => ['value' => $m->value, 'text' => $m->label()], self::cases());
    }
}
