<?php

namespace Modules\Menu\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Menu\Data\Requests\StoreMenuData;
use Modules\Menu\Models\Menu;

class StoreMenuAction
{
    use AsAction;

    public function handle(StoreMenuData $data): Menu
    {
        return DB::transaction(function () use ($data) {
            self::assertUnique($data, null);

            $menu = Menu::create([
                'customer_id' => $data->customer_id,
                'menu_date'   => $data->menu_date,
                'meal_time'   => $data->meal_time,
                'note'        => $data->note,
            ]);

            foreach (collect($data->dishes)->values() as $i => $dish) {
                $menu->dishes()->create([
                    'sort_order'       => $i + 1,
                    'dish_name'        => $dish->dish_name,
                    'main_ingredients' => $dish->main_ingredients,
                    'servings'         => $dish->servings,
                ]);
            }

            return $menu;
        });
    }

    /** Mỗi cơ sở chỉ có một thực đơn cho mỗi ngày + bữa ăn (để đồng bộ xuống Sổ kiểm thực Bước 2 không mơ hồ). */
    public static function assertUnique(StoreMenuData $data, ?string $ignoreId): void
    {
        $exists = Menu::query()
            ->where('customer_id', $data->customer_id)
            ->whereDate('menu_date', $data->menu_date)
            ->where('meal_time', $data->meal_time->value)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['meal_time' => 'Cơ sở này đã có thực đơn cho ngày và bữa ăn đã chọn.']);
        }
    }
}
