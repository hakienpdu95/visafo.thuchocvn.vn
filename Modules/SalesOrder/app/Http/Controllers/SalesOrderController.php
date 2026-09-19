<?php

namespace Modules\SalesOrder\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\SalesOrder\Models\SalesOrder;

class SalesOrderController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(SalesOrder::class, 'sales_order');
    }

    public function index()
    {
        $statuses = collect(SalesOrder::statusLabels())
            ->map(fn ($label, $value) => ['value' => $value, 'text' => $label])
            ->values()
            ->all();

        return view('salesorder::sales-orders.index', compact('statuses'));
    }

    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->load(['importedBy', 'items.product']);

        return view('salesorder::sales-orders.show', compact('salesOrder'));
    }
}
