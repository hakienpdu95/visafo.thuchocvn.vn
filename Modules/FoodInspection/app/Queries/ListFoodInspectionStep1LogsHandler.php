<?php

namespace Modules\FoodInspection\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\FoodInspection\Models\FoodInspectionStep1Log;

class ListFoodInspectionStep1LogsHandler implements QueryHandlerInterface
{
    public const RESULT_FAILED = 'failed';
    public const RESULT_PASSED = 'passed';

    private const SORTABLE = ['inspected_at', 'inspection_location', 'failed_items_count', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListFoodInspectionStep1LogsQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'inspected_at';
        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = FoodInspectionStep1Log::query()->visibleTo()->with('inspector:id,name')->withCount('details');

        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(fn (Builder $sub) => $sub->where('inspection_location', 'like', $term)
                ->orWhere('customer_name', 'like', $term)
                ->orWhereHas('details', fn (Builder $d) => $d->where('vendor_name', 'like', $term)
                    ->orWhere('deliverer_name', 'like', $term)
                    ->orWhere('product_name', 'like', $term)));
        }

        if ($query->inspectorId) {
            $q->where('inspected_by', $query->inspectorId);
        }

        if ($query->foodGroup) {
            $q->whereHas('details', fn (Builder $d) => $d->where('food_group', $query->foodGroup));
        }

        if ($query->result === self::RESULT_FAILED) {
            $q->where('failed_items_count', '>', 0);
        } elseif ($query->result === self::RESULT_PASSED) {
            $q->where('failed_items_count', 0);
        }

        if ($query->dateFrom) {
            $q->whereDate('inspected_at', '>=', $query->dateFrom);
        }

        if ($query->dateTo) {
            $q->whereDate('inspected_at', '<=', $query->dateTo);
        }

        $q->orderBy($sortField, $sortDir);
        if ($sortField !== 'inspected_at') {
            $q->orderByDesc('inspected_at');
        }
        $q->orderBy('id', $sortDir);

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
