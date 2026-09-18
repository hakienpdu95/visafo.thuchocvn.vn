<?php

namespace Modules\GoodsReceipt\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\GoodsReceipt\Models\GoodsReceipt;

class ListGoodsReceiptsHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['misa_ref_id', 'supplier_name', 'receipt_date', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListGoodsReceiptsQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'created_at';
        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = GoodsReceipt::query()->withCount('items')->with('vendor');

        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('misa_ref_id', 'like', $term)
                    ->orWhere('supplier_name', 'like', $term);
            });
        }

        if ($query->vendorId !== null && $query->vendorId !== '') {
            $q->where('vendor_id', $query->vendorId);
        }

        if ($query->dateFrom !== null && $query->dateFrom !== '') {
            $q->whereDate('receipt_date', '>=', $query->dateFrom);
        }

        if ($query->dateTo !== null && $query->dateTo !== '') {
            $q->whereDate('receipt_date', '<=', $query->dateTo);
        }

        $q->orderBy($sortField, $sortDir);
        if ($sortField !== 'id') {
            $q->orderBy('id', $sortDir);
        }

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
