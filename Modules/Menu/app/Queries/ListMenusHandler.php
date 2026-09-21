<?php

namespace Modules\Menu\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Menu\Models\Menu;

class ListMenusHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['menu_date', 'meal_time', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListMenusQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'menu_date';
        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = Menu::query()->with('customer:id,name')->withCount('dishes')->withSum('dishes as total_servings', 'servings');

        if ($query->search) {
            $term = '%' . $query->search . '%';
            $q->where(fn (Builder $s) => $s->whereHas('customer', fn (Builder $c) => $c->where('name', 'like', $term))
                ->orWhereHas('dishes', fn (Builder $d) => $d->where('dish_name', 'like', $term)->orWhere('main_ingredients', 'like', $term)));
        }
        if ($query->customerId) {
            $q->where('customer_id', $query->customerId);
        }
        if ($query->mealTime) {
            $q->where('meal_time', $query->mealTime);
        }
        if ($query->dateFrom) {
            $q->whereDate('menu_date', '>=', $query->dateFrom);
        }
        if ($query->dateTo) {
            $q->whereDate('menu_date', '<=', $query->dateTo);
        }

        $q->orderBy($sortField, $sortDir);
        if ($sortField !== 'menu_date') {
            $q->orderByDesc('menu_date');
        }

        return $q->orderBy('id', $sortDir)->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
