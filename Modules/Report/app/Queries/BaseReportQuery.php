<?php

namespace Modules\Report\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

abstract class BaseReportQuery
{
    protected const UNIT_EXPR = "LOWER(TRIM(COALESCE(i.unit_raw, '')))";

    protected function itemsQuery(ReportFilters $filters): Builder
    {
        $q = DB::table('sales_order_items as i')
            ->join('sales_orders as o', 'o.id', '=', 'i.order_id')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->whereNull('i.deleted_at')
            ->whereNull('o.deleted_at');

        if ($filters->dateFrom) {
            $q->where('o.delivery_date', '>=', $filters->dateFrom);
        }

        if ($filters->dateTo) {
            $q->where('o.delivery_date', '<=', $filters->dateTo);
        }

        if ($filters->customer !== null && $filters->customer !== '') {
            $q->where('o.customer_name', $filters->customer);
        }

        if ($filters->unit !== null && $filters->unit !== '') {
            $q->whereRaw(self::UNIT_EXPR . ' = ?', [mb_strtolower(trim($filters->unit))]);
        }

        return $q;
    }

    protected static function qty(mixed $value): float
    {
        return round((float) $value, 3);
    }
}
