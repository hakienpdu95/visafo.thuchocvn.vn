<?php

namespace Modules\FoodInspection\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\FoodInspection\Models\FoodInspectionStep3Log;

class ListFoodInspectionStep3LogsHandler implements QueryHandlerInterface
{
    public const RESULT_FAILED = 'failed';
    public const RESULT_PASSED = 'passed';

    private const SORTABLE = ['inspection_date', 'customer_name', 'failed_items_count', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListFoodInspectionStep3LogsQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'inspection_date';
        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = FoodInspectionStep3Log::query()->withCount('details');

        if ($query->search) {
            $term = '%' . $query->search . '%';
            $q->where(fn (Builder $s) => $s->where('customer_name', 'like', $term)->orWhere('location_name', 'like', $term)
                ->orWhereHas('details', fn (Builder $d) => $d->where('dish_name', 'like', $term)));
        }
        if ($query->customerId) {
            $q->where('customer_id', $query->customerId);
        }
        if ($query->result === self::RESULT_FAILED) {
            $q->where('failed_items_count', '>', 0);
        } elseif ($query->result === self::RESULT_PASSED) {
            $q->where('failed_items_count', 0);
        }
        if ($query->dateFrom) {
            $q->whereDate('inspection_date', '>=', $query->dateFrom);
        }
        if ($query->dateTo) {
            $q->whereDate('inspection_date', '<=', $query->dateTo);
        }

        $q->orderBy($sortField, $sortDir);
        if ($sortField !== 'inspection_date') {
            $q->orderByDesc('inspection_date');
        }

        return $q->orderBy('id', $sortDir)->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
