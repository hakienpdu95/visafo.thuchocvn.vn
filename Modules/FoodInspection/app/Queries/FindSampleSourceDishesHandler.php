<?php

namespace Modules\FoodInspection\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Database\Eloquent\Builder;
use Modules\FoodInspection\Models\FoodInspectionStep3Detail;
use Modules\FoodInspection\Support\SampleVolume;
use Modules\Menu\Queries\FindMenuDishesHandler;
use Modules\Menu\Queries\FindMenuDishesQuery;

/**
 * Danh sách món cần lưu mẫu, kế thừa (không cho gõ tay để tránh lệch hồ sơ):
 *  (1) ưu tiên Sổ Bước 3 của cùng khách hàng + ngày + bữa (chỉ món cảm quan đạt — món đã loại bỏ không được ăn nên không lưu mẫu);
 *  (2) nếu bữa đó chưa có Sổ Bước 3 → lấy toàn bộ món trong Thực đơn.
 * Khối lượng gợi ý: 100g (đặc) / 150ml (lỏng).
 */
class FindSampleSourceDishesHandler implements QueryHandlerInterface
{
    /** @return array{found: bool, source: ?string, rows: array<int, array<string, mixed>>} */
    public function handle(QueryInterface $query): array
    {
        /** @var FindSampleSourceDishesQuery $query */
        $step3 = FoodInspectionStep3Detail::query()
            ->where('meal_time', $query->mealTime)
            ->whereHas('log', fn (Builder $l) => $l->where('customer_id', $query->customerId)->whereDate('inspection_date', $query->date))
            ->orderBy('line_no')
            ->get()
            ->reject(fn (FoodInspectionStep3Detail $d) => $d->isFailed())
            ->unique(fn (FoodInspectionStep3Detail $d) => $d->menu_dish_id ?: mb_strtolower($d->dish_name))
            ->values();

        if ($step3->isNotEmpty()) {
            return ['found' => true, 'source' => 'step3', 'rows' => $step3->map(fn (FoodInspectionStep3Detail $d) => [
                'source'          => 'step3',
                'step3_detail_id' => $d->id,
                'menu_dish_id'    => $d->menu_dish_id,
                'meal_time'       => $d->meal_time->value,
                'dish_name'       => $d->dish_name,
                'portion_qty'     => $d->quantity,
                'sample_volume'   => SampleVolume::suggest($d->dish_name),
            ])->all()];
        }

        $menu = app(FindMenuDishesHandler::class)->handle(new FindMenuDishesQuery($query->customerId, $query->date, $query->mealTime));
        if ($menu === null) {
            return ['found' => false, 'source' => null, 'rows' => []];
        }

        return ['found' => true, 'source' => 'menu', 'rows' => collect($menu['dishes'])->map(fn (array $d) => [
            'source'          => 'menu',
            'step3_detail_id' => null,
            'menu_dish_id'    => $d['menu_dish_id'],
            'meal_time'       => $d['meal_time'],
            'dish_name'       => $d['dish_name'],
            'portion_qty'     => $d['servings'],
            'sample_volume'   => SampleVolume::suggest($d['dish_name']),
        ])->all()];
    }
}
