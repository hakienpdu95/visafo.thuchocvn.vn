<?php

namespace Modules\GoodsReceipt\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\GoodsReceipt\Actions\Backend\ImportGoodsReceiptsAction;
use Modules\GoodsReceipt\Models\GoodsReceipt;

class GoodsReceiptImportController extends Controller
{
    public function create()
    {
        $this->authorize('create', GoodsReceipt::class);

        return view('goodsreceipt::goods-receipts.import');
    }

    public function store(Request $request, ImportGoodsReceiptsAction $action): RedirectResponse
    {
        $this->authorize('create', GoodsReceipt::class);

        $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'mimes:xls,xlsx', 'max:10240'],
        ]);

        $summary = $action->handle($request->file('files'), $request->user()?->id);

        return redirect()->route('backend.goods-receipts.import')
            ->with('import_summary', $summary->toArray());
    }
}
