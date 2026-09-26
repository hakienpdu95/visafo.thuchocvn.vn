<?php

namespace Modules\SalesOrder\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\SalesOrder\Models\SalesOrder;

class ListSalesOrdersHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['misa_ref_id', 'customer_name', 'status', 'delivery_date', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListSalesOrdersQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'created_at';
        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = SalesOrder::query()->visibleTo()->withCount('items');

        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('misa_ref_id', 'like', $term)
                    ->orWhere('customer_name', 'like', $term)
                    ->orWhere('delivery_address', 'like', $term);
            });
        }

        if ($query->status !== null && $query->status !== '') {
            $q->where('status', $query->status);
        }

        if ($query->dateFrom !== null && $query->dateFrom !== '') {
            $q->whereDate('created_at', '>=', $query->dateFrom);
        }

        if ($query->dateTo !== null && $query->dateTo !== '') {
            $q->whereDate('created_at', '<=', $query->dateTo);
        }

        $q->orderBy($sortField, $sortDir)->orderBy('id', $sortDir);

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
