<?php

namespace Modules\Warehouse\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Warehouse\Models\OutboundOrder;

class ListOutboundOrdersHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['order_number', 'dealer_name', 'ordered_at', 'status'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListOutboundOrdersQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'ordered_at';
        $sortDir   = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = OutboundOrder::query()->withCount('pickedBatches');

        if ($query->status !== null && $query->status !== '') {
            $q->where('status', $query->status);
        }

        $q->orderBy($sortField, $sortDir);
        if ($sortField !== 'id') {
            $q->orderBy('id', $sortDir);
        }

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
