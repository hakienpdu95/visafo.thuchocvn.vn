<?php

namespace Modules\Menu\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Menu\Data\Requests\MenuDishData;
use Modules\Menu\Data\Requests\StoreMenuData;
use Modules\Menu\Models\Menu;

class UpdateMenuAction
{
    use AsAction;

    /** Món có id → cập nhật tại chỗ; không id → thêm mới; món cũ không còn trong dữ liệu gửi lên → xóa. */
    public function handle(Menu $menu, StoreMenuData $data): Menu
    {
        return DB::transaction(function () use ($menu, $data) {
            StoreMenuAction::assertUnique($data, $menu->id);

            $existing = $menu->dishes()->get()->keyBy('id');
            $incoming = collect($data->dishes)->values();

            if ($incoming->first(fn (MenuDishData $d) => $d->id !== null && ! $existing->has($d->id))) {
                throw ValidationException::withMessages(['dishes' => 'Có món ăn không thuộc thực đơn này.']);
            }

            $menu->fill([
                'customer_id' => $data->customer_id,
                'menu_date'   => $data->menu_date,
                'meal_time'   => $data->meal_time,
                'note'        => $data->note,
            ])->save();
            $menu->touch();

            foreach ($incoming as $i => $dish) {
                $attributes = [
                    'sort_order'       => $i + 1,
                    'dish_name'        => $dish->dish_name,
                    'main_ingredients' => $dish->main_ingredients,
                    'servings'         => $dish->servings,
                ];

                $dish->id !== null ? $existing->get($dish->id)->update($attributes) : $menu->dishes()->create($attributes);
            }

            $keptIds = $incoming->pluck('id')->filter()->all();
            $existing->reject(fn ($d) => in_array($d->id, $keptIds, true))->each->delete();

            return $menu->refresh();
        });
    }
}
