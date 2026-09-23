<?php

namespace Modules\Report\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;

class VolumeReportHandler extends BaseReportQuery implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): array
    {
        $base = $this->itemsQuery($query);
        $unit = self::UNIT_EXPR;

        $customers = (clone $base)
            ->selectRaw("o.customer_name, MAX(i.unit_raw) as unit, SUM(i.requested_qty) as qty, COUNT(DISTINCT o.id) as orders, COUNT(DISTINCT i.product_id) as products")
            ->groupBy('o.customer_name')->groupByRaw($unit)
            ->orderByDesc('qty')
            ->get()
            ->map(fn ($r) => [
                'customer_name' => $r->customer_name ?: '(Không tên)',
                'unit'          => $r->unit,
                'qty'           => self::qty($r->qty),
                'orders'        => (int) $r->orders,
                'products'      => (int) $r->products,
            ])->all();

        $products = (clone $base)
            ->selectRaw("i.product_id, MAX(COALESCE(p.name, i.product_name_raw)) as product_name, MAX(p.sku) as sku, MAX(i.unit_raw) as unit,
                SUM(i.requested_qty) as qty, COUNT(DISTINCT o.customer_name) as customers, COUNT(DISTINCT o.id) as orders")
            ->groupBy('i.product_id')->groupByRaw($unit)
            ->orderByDesc('qty')
            ->get()
            ->map(fn ($r) => [
                'product_name' => $r->product_name,
                'sku'          => $r->sku,
                'unit'         => $r->unit,
                'qty'          => self::qty($r->qty),
                'customers'    => (int) $r->customers,
                'orders'       => (int) $r->orders,
            ])->all();

        $trend = (clone $base)
            ->selectRaw("o.delivery_date as day, MAX(i.unit_raw) as unit, SUM(i.requested_qty) as qty")
            ->whereNotNull('o.delivery_date')
            ->groupBy('o.delivery_date')->groupByRaw($unit)
            ->orderBy('o.delivery_date')
            ->get()
            ->map(fn ($r) => [
                'day'  => date('d/m/Y', strtotime($r->day)),
                'unit' => $r->unit,
                'qty'  => self::qty($r->qty),
            ])->all();

        return compact('customers', 'products', 'trend');
    }
}
