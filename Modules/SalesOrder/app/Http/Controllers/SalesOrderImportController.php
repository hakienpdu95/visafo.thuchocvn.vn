<?php

namespace Modules\SalesOrder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\SalesOrder\Actions\Backend\ImportSalesOrdersAction;
use Modules\SalesOrder\Models\SalesOrder;
use Modules\SalesOrder\Support\UploadLimits;

class SalesOrderImportController extends Controller
{
    public function create()
    {
        $this->authorize('create', SalesOrder::class);

        return view('salesorder::sales-orders.import', [
            'defaultDeliveryDate' => now()->addDay()->toDateString(),
            'uploadLimits'        => UploadLimits::toArray(),
        ]);
    }

    public function store(Request $request, ImportSalesOrdersAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize('create', SalesOrder::class);

        $request->validate([
            'files'         => ['required_without:file_names', 'array'],
            'file_names'    => ['nullable', 'array', 'max:1000'],
            'file_names.*'  => ['string', 'max:255'],
            'delivery_date' => ['required', 'date'],
        ], [
            'files.required_without' => 'Vui lòng chọn ít nhất 1 file Excel.',
            'delivery_date.required' => 'Vui lòng chọn ngày giao hàng.',
            'delivery_date.date'     => 'Ngày giao hàng không hợp lệ.',
        ]);

        $summary = $action->handle(
            $request->file('files', []),
            $request->user()?->id,
            $request->input('delivery_date'),
            $request->input('file_names', []),
        );

        if ($request->wantsJson()) {
            return response()->json($summary->toArray());
        }

        return redirect()->route('backend.sales-orders.import')
            ->with('import_summary', $summary->toArray());
    }
}
