<?php

namespace Modules\FoodInspection\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Database\Eloquent\Builder;
use Modules\FoodInspection\Models\FoodInspectionStep2Detail;
use Modules\Menu\Queries\FindMenuDishesHandler;
use Modules\Menu\Queries\FindMenuDishesQuery;

/**
 * Hai nguồn, cùng khách hàng + ngày + bữa ăn:
 *  (1) món đã nấu và ĐẠT ở Sổ Bước 2;
 *  (2) món trong Thực đơn chưa qua Bước 2 (đồ ăn sẵn, tráng miệng — sữa chua, bánh, trái cây...).
 * Món đã qua Bước 2 nhưng KHÔNG đạt bị loại hẳn: không được đưa đi chia suất.
 */
class FindStep3SourceDishesHandler implements QueryHandlerInterface
{
    /** @return array{found: bool, rows: array<int, array<string, mixed>>, from_step2: int, from_menu: int} */
    public function handle(QueryInterface $query): array
    {
        /** @var FindStep3SourceDishesQuery $query */
        $step2 = FoodInspectionStep2Detail::query()
            ->where('meal_time', $query->mealTime)
            ->whereHas('log', fn (Builder $l) => $l->where('customer_id', $query->customerId)->whereDate('inspection_date', $query->date))
            ->orderBy('line_no')
            ->get();

        $cookedMenuDishIds = $step2->pluck('menu_dish_id')->filter()->all();

        $rows = $step2->reject(fn (FoodInspectionStep2Detail $d) => $d->isFailed())
            ->unique(fn (FoodInspectionStep2Detail $d) => $d->menu_dish_id ?: mb_strtolower($d->dish_name))
            ->map(fn (FoodInspectionStep2Detail $d) => [
                'source'          => 'step2',
                'step2_detail_id' => $d->id,
                'menu_dish_id'    => $d->menu_dish_id,
                'meal_time'       => $d->meal_time->value,
                'dish_name'       => $d->dish_name,
                'quantity'        => $d->quantity,
            ])->values()->all();
        $fromStep2 = count($rows);

        $menu = app(FindMenuDishesHandler::class)->handle(new FindMenuDishesQuery($query->customerId, $query->date, $query->mealTime));
        foreach ($menu['dishes'] ?? [] as $dish) {
            if (in_array($dish['menu_dish_id'], $cookedMenuDishIds, true)) {
                continue;
            }
            $rows[] = [
                'source'          => 'menu',
                'step2_detail_id' => null,
                'menu_dish_id'    => $dish['menu_dish_id'],
                'meal_time'       => $dish['meal_time'],
                'dish_name'       => $dish['dish_name'],
                'quantity'        => $dish['servings'],
            ];
        }

        return ['found' => $rows !== [], 'rows' => $rows, 'from_step2' => $fromStep2, 'from_menu' => count($rows) - $fromStep2];
    }
}
