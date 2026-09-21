<?php

namespace Modules\Menu\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuDish;

class FindMenuDishesHandler implements QueryHandlerInterface
{
    /** @return array{menu_id: string, dishes: array<int, array<string, mixed>>}|null  null = không có thực đơn */
    public function handle(QueryInterface $query): ?array
    {
        /** @var FindMenuDishesQuery $query */
        $menu = Menu::query()
            ->where('customer_id', $query->customerId)
            ->whereDate('menu_date', $query->date)
            ->where('meal_time', $query->mealTime)
            ->with('dishes')
            ->latest('id')
            ->first();

        if (! $menu) {
            return null;
        }

        return [
            'menu_id' => $menu->id,
            'dishes'  => $menu->dishes->map(fn (MenuDish $d) => [
                'menu_dish_id'     => $d->id,
                'meal_time'        => $menu->meal_time->value,
                'dish_name'        => $d->dish_name,
                'main_ingredients' => $d->main_ingredients,
                'servings'         => $d->servings,
            ])->all(),
        ];
    }
}
