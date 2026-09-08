<?php

namespace Modules\Warehouse\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Product\Models\Product;
use Modules\Vendor\Models\Vendor;
use Modules\Warehouse\Actions\Backend\CompleteInboundReceiptAction;
use Modules\Warehouse\Actions\Backend\DestroyInboundReceiptAction;
use Modules\Warehouse\Actions\Backend\StoreInboundReceiptAction;
use Modules\Warehouse\Actions\Backend\UpdateInboundReceiptAction;
use Modules\Warehouse\Data\Requests\StoreInboundReceiptData;
use Modules\Warehouse\Data\Requests\UpdateInboundReceiptData;
use Modules\Warehouse\Enums\InboundReceiptStatus;
use Modules\Warehouse\Models\InboundReceipt;
use Modules\Warehouse\Queries\GetInboundReceiptHandler;
use Modules\Warehouse\Queries\GetInboundReceiptQuery;

class InboundReceiptController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(InboundReceipt::class, 'inbound_receipt');
    }

    public function index()
    {
        $statuses = collect(InboundReceiptStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        return view('warehouse::inbound_receipts.index', compact('statuses'));
    }

    public function create()
    {
        $vendors = Vendor::orderBy('name')->get();

        return view('warehouse::inbound_receipts.create', compact('vendors'));
    }

    public function store(Request $request, StoreInboundReceiptAction $action): RedirectResponse
    {
        $data           = StoreInboundReceiptData::validateAndCreate($request->all());
        $inboundReceipt = $action->handle($data);

        return redirect()->route('backend.inbound-receipts.show', $inboundReceipt)
            ->with('success', 'Phiếu nhập kho "' . $inboundReceipt->receipt_number . '" đã được tạo thành công.');
    }

    public function show(InboundReceipt $inboundReceipt, GetInboundReceiptHandler $handler)
    {
        $inboundReceipt = $handler->handle(new GetInboundReceiptQuery($inboundReceipt));
        $products       = Product::orderBy('name')->get();

        return view('warehouse::inbound_receipts.show', compact('inboundReceipt', 'products'));
    }

    public function edit(InboundReceipt $inboundReceipt)
    {
        $vendors = Vendor::orderBy('name')->get();

        return view('warehouse::inbound_receipts.edit', compact('inboundReceipt', 'vendors'));
    }

    public function update(Request $request, InboundReceipt $inboundReceipt, UpdateInboundReceiptAction $action): RedirectResponse
    {
        $data = UpdateInboundReceiptData::validateAndCreate($request->all());
        $action->handle($inboundReceipt, $data);

        return redirect()->route('backend.inbound-receipts.show', $inboundReceipt)
            ->with('success', 'Cập nhật phiếu nhập kho thành công.');
    }

    public function complete(InboundReceipt $inboundReceipt, CompleteInboundReceiptAction $action): RedirectResponse
    {
        $this->authorize('update', $inboundReceipt);

        $action->handle($inboundReceipt);

        return redirect()->route('backend.inbound-receipts.show', $inboundReceipt)
            ->with('success', 'Đã hoàn tất phiếu nhập và sinh tem truy vết cho các lô hàng.');
    }

    public function destroy(InboundReceipt $inboundReceipt, DestroyInboundReceiptAction $action): RedirectResponse
    {
        $number = $action->handle($inboundReceipt);

        return redirect()->route('backend.inbound-receipts.index')
            ->with('success', 'Đã xóa phiếu nhập kho "' . $number . '".');
    }
}
