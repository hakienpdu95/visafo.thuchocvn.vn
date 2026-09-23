<?php

namespace Modules\Report\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;

class PickingReportHandler extends BaseReportQuery implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): array
    {
        $lines = $this->itemsQuery($query)
            ->selectRaw('i.product_id, COALESCE(p.name, i.product_name_raw) as product_name, p.sku, i.unit_raw,
                ' . self::UNIT_EXPR . ' as unit_key, o.customer_name, SUM(i.requested_qty) as qty')
            ->groupBy('i.product_id', 'product_name', 'p.sku', 'i.unit_raw', 'unit_key', 'o.customer_name')
            ->get();

        $rows = $lines
            ->groupBy(fn ($l) => $l->product_id . '|' . $l->unit_key)
            ->map(function ($group) {
                $first = $group->first();
                $customers = $group
                    ->groupBy(fn ($l) => $l->customer_name ?: '(Không tên)')
                    ->map(fn ($g, $name) => ['customer_name' => $name, 'qty' => self::qty($g->sum('qty'))])
                    ->sortByDesc('qty')->values();

                return [
                    'product_name' => $first->product_name,
                    'sku'          => $first->sku,
                    'unit'         => $first->unit_raw,
                    'total_qty'    => self::qty($group->sum('qty')),
                    'customers'    => $customers->all(),
                ];
            })
            ->sortBy('product_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return [
            'rows'      => $rows->all(),
            'customers' => $lines->pluck('customer_name')->filter()->unique()->count(),
        ];
    }
}
