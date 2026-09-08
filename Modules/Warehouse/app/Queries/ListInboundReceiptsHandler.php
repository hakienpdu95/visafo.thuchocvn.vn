<?php

namespace Modules\Warehouse\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Warehouse\Models\InboundReceipt;

class ListInboundReceiptsHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['receipt_number', 'received_date', 'status', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListInboundReceiptsQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'received_date';
        $sortDir   = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = InboundReceipt::query()->with('vendor')->withCount('batches');

        if ($query->search !== null && $query->search !== '') {
            $q->where('receipt_number', 'like', '%' . $query->search . '%');
        }

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
