<?php

namespace Modules\Menu\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Menu\Models\Menu;

class DestroyMenuAction
{
    use AsAction;

    /** Xóa mềm thực đơn và các món; trả về nhãn để hiện thông báo. */
    public function handle(Menu $menu): string
    {
        return DB::transaction(function () use ($menu) {
            $label = $menu->menu_date?->format('d/m/Y') . ' — ' . $menu->meal_time->label();
            $menu->dishes()->get()->each->delete();
            $menu->delete();

            return $label;
        });
    }
}
