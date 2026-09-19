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

        return view('salesorder::sales-orders.import');
    }

    public function store(Request $request, ImportSalesOrdersAction $action): RedirectResponse
    {
        $this->authorize('create', SalesOrder::class);

        $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'mimes:xls,xlsx', 'max:10240'],
        ]);

        $summary = $action->handle($request->file('files'), $request->user()?->id);

        return redirect()->route('backend.sales-orders.import')
            ->with('import_summary', $summary->toArray());
    }
}
