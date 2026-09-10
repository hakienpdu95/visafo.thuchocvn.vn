<?php

namespace Modules\Customer\Enums;

enum MealModel: string
{
    case CookAtSchool    = 'cook_at_school';
    case PreCookedMeal   = 'pre_cooked_meal';
    case IngredientSupply = 'ingredient_supply';

    public function label(): string
    {
        return match ($this) {
            self::CookAtSchool     => 'Nấu ăn tại trường',
            self::PreCookedMeal    => 'Cung cấp suất ăn sẵn',
            self::IngredientSupply => 'Chỉ cung cấp nguyên liệu',
        };
    }
}
