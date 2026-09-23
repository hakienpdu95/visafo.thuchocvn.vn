<?php

namespace Modules\SalesOrder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\SalesOrder\Actions\Backend\ImportSalesOrdersAction;
use Modules\SalesOrder\Models\SalesOrder;

class SalesOrderImportController extends Controller
{
    public function create()
    {
        $this->authorize('create', SalesOrder::class);

        return view('salesorder::sales-orders.import', [
            'defaultDeliveryDate' => now()->addDay()->toDateString(),
        ]);
    }

    public function store(Request $request, ImportSalesOrdersAction $action): RedirectResponse
    {
        $this->authorize('create', SalesOrder::class);

        $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'mimes:xls,xlsx', 'max:10240'],
            'delivery_date' => ['required', 'date'],
        ], [
            'delivery_date.required' => 'Vui lòng chọn ngày giao hàng.',
            'delivery_date.date'     => 'Ngày giao hàng không hợp lệ.',
        ]);

        $summary = $action->handle($request->file('files'), $request->user()?->id, $request->input('delivery_date'));

        return redirect()->route('backend.sales-orders.import')
            ->with('import_summary', $summary->toArray());
    }
}
