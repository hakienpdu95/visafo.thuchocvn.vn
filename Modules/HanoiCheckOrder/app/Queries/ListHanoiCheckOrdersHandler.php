<?php

namespace Modules\HanoiCheckOrder\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\SalesOrder\Models\SalesOrder;

class ListHanoiCheckOrdersHandler implements QueryHandlerInterface
{
    private const SORTS = [
        'delivery_date'         => 'sales_orders.delivery_date',
        'misa_ref_id'           => 'sales_orders.misa_ref_id',
        'customer_name'         => 'sales_orders.customer_name',
        'created_at'            => 'sales_orders.created_at',
        'hanoicheck_order_code' => 'hcl.hanoicheck_order_code',
        'linked_at'             => 'hcl.linked_at',
    ];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        $sort = self::SORTS[$query->sortField] ?? self::SORTS['delivery_date'];
        $dir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        return SalesOrder::query()
            ->withoutTenant()
            ->leftJoin('hanoicheck_order_links as hcl', 'hcl.sales_order_id', '=', 'sales_orders.id')
            ->leftJoin('users as hcl_user', 'hcl_user.id', '=', 'hcl.linked_by')
            ->select('sales_orders.*')
            ->addSelect([
                'hcl.hanoicheck_order_code',
                'hcl.note as hanoicheck_note',
                'hcl.linked_at as hanoicheck_linked_at',
                'hcl_user.name as hanoicheck_linked_by_name',
            ])
            ->when(filled($query->search), function ($q) use ($query) {
                $term = '%' . addcslashes(trim($query->search), '%_\\') . '%';
                $q->where(fn ($w) => $w->where('sales_orders.misa_ref_id', 'like', $term)
                    ->orWhere('sales_orders.customer_name', 'like', $term)
                    ->orWhere('sales_orders.delivery_code', 'like', $term)
                    ->orWhere('hcl.hanoicheck_order_code', 'like', $term));
            })
            ->when($query->link === ListHanoiCheckOrdersQuery::LINK_LINKED, fn ($q) => $q->whereNotNull('hcl.id'))
            ->when($query->link === ListHanoiCheckOrdersQuery::LINK_UNLINKED, fn ($q) => $q->whereNull('hcl.id'))
            ->when(filled($query->status), fn ($q) => $q->where('sales_orders.status', $query->status))
            ->when(filled($query->dateFrom), fn ($q) => $q->whereDate('sales_orders.delivery_date', '>=', $query->dateFrom))
            ->when(filled($query->dateTo), fn ($q) => $q->whereDate('sales_orders.delivery_date', '<=', $query->dateTo))
            ->orderBy($sort, $dir)
            ->orderBy('sales_orders.id', 'desc')
            ->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
