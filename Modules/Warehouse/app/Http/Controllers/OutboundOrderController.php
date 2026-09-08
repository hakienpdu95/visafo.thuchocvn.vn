<?php

namespace Modules\Warehouse\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Product\Models\Product;
use Modules\Warehouse\Actions\Backend\ActivateOutboundOrderTagsAction;
use Modules\Warehouse\Actions\Backend\AddPickedBatchAction;
use Modules\Warehouse\Actions\Backend\CancelOutboundOrderAction;
use Modules\Warehouse\Actions\Backend\CompleteOutboundOrderAction;
use Modules\Warehouse\Actions\Backend\RemovePickedBatchAction;
use Modules\Warehouse\Actions\Backend\StoreOutboundOrderAction;
use Modules\Warehouse\Actions\Backend\UpdateOutboundOrderAction;
use Modules\Warehouse\Data\Requests\AddPickedBatchData;
use Modules\Warehouse\Data\Requests\StoreOutboundOrderData;
use Modules\Warehouse\Data\Requests\UpdateOutboundOrderData;
use Modules\Warehouse\Enums\OutboundOrderStatus;
use Modules\Warehouse\Models\OutboundOrder;
use Modules\Warehouse\Models\OutboundPickedBatch;
use Modules\Warehouse\Queries\GetFefoSuggestionHandler;
use Modules\Warehouse\Queries\GetFefoSuggestionQuery;
use Modules\Warehouse\Queries\GetOutboundOrderHandler;
use Modules\Warehouse\Queries\GetOutboundOrderQuery;
use Modules\Warehouse\Queries\GetOutboundOrderTagAllocationsHandler;
use Modules\Warehouse\Queries\GetOutboundOrderTagAllocationsQuery;
use Spatie\LaravelPdf\Facades\Pdf;

class OutboundOrderController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(OutboundOrder::class, 'order');
    }

    public function index()
    {
        $statuses = collect(OutboundOrderStatus::cases())->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])->all();

        return view('warehouse::outbound_orders.index', compact('statuses'));
    }

    public function create()
    {
        return view('warehouse::outbound_orders.create');
    }

    public function store(Request $request, StoreOutboundOrderAction $action): RedirectResponse
    {
        $data  = StoreOutboundOrderData::validateAndCreate($request->all());
        $order = $action->handle($data);

        return redirect()->route('backend.outbound-orders.show', $order)
            ->with('success', 'Đã tạo đơn xuất buôn "' . $order->order_number . '".');
    }

    public function edit(OutboundOrder $order)
    {
        return view('warehouse::outbound_orders.edit', compact('order'));
    }

    public function update(Request $request, OutboundOrder $order, UpdateOutboundOrderAction $action): RedirectResponse
    {
        $data = UpdateOutboundOrderData::validateAndCreate($request->all());
        $action->handle($order, $data);

        return redirect()->route('backend.outbound-orders.show', $order)
            ->with('success', 'Cập nhật đơn xuất buôn thành công.');
    }

    public function show(
        Request $request,
        OutboundOrder $order,
        GetOutboundOrderHandler $handler,
        GetFefoSuggestionHandler $fefoHandler,
        GetOutboundOrderTagAllocationsHandler $allocationsHandler,
    ) {
        $order       = $handler->handle(new GetOutboundOrderQuery($order));
        $products    = Product::orderBy('name')->get();
        $allocations = $allocationsHandler->handle(new GetOutboundOrderTagAllocationsQuery($order));
        $tagsTotal   = $order->tags()->count();
        $pendingActivationCount = $order->tags()->where('status', \Modules\Warehouse\Enums\RetailItemTagStatus::Transferred->value)->count();

        $fefoSuggestions = null;
        $selectedProductId = $request->input('product_id');
        if ($selectedProductId) {
            $fefoSuggestions = $fefoHandler->handle(new GetFefoSuggestionQuery(
                productId: $selectedProductId,
                requestedQty: (int) $request->input('requested_qty', 0),
            ));
        }

        return view('warehouse::outbound_orders.show', compact('order', 'products', 'fefoSuggestions', 'selectedProductId', 'allocations', 'tagsTotal', 'pendingActivationCount'));
    }

    public function packingSlip(OutboundOrder $order, GetOutboundOrderHandler $handler, GetOutboundOrderTagAllocationsHandler $allocationsHandler)
    {
        $this->authorize('view', $order);

        $order       = $handler->handle(new GetOutboundOrderQuery($order));
        $allocations = $allocationsHandler->handle(new GetOutboundOrderTagAllocationsQuery($order));

        return Pdf::view('warehouse::outbound_orders.packing_slip', compact('order', 'allocations'))
            ->format('a4')
            ->withBrowsershot(fn ($browsershot) => $browsershot->setChromePath(config('warehouse.chrome_path', '/usr/bin/google-chrome'))->noSandbox())
            ->download('phieu-xuat-kho-' . $order->order_number . '.pdf');
    }

    public function addBatch(Request $request, OutboundOrder $order, AddPickedBatchAction $action): RedirectResponse
    {
        $this->authorize('update', $order);

        $data = AddPickedBatchData::validateAndCreate($request->all());
        $action->handle($order, $data);

        return redirect()->route('backend.outbound-orders.show', $order)
            ->with('success', 'Đã thêm lô hàng vào đơn xuất buôn.');
    }

    public function removeBatch(OutboundOrder $order, OutboundPickedBatch $pickedBatch, RemovePickedBatchAction $action): RedirectResponse
    {
        $this->authorize('update', $order);
        abort_unless($pickedBatch->outbound_order_id === $order->id, 404);

        $action->handle($pickedBatch);

        return redirect()->route('backend.outbound-orders.show', $order)
            ->with('success', 'Đã bỏ lô hàng khỏi đơn xuất buôn.');
    }

    public function complete(OutboundOrder $order, CompleteOutboundOrderAction $action): RedirectResponse
    {
        $this->authorize('update', $order);

        $action->handle($order);

        return redirect()->route('backend.outbound-orders.show', $order)
            ->with('success', 'Đã xuất kho đơn hàng — tồn kho và tem QR đã được cập nhật.');
    }

    public function activateTags(OutboundOrder $order, ActivateOutboundOrderTagsAction $action): RedirectResponse
    {
        $this->authorize('update', $order);

        $activated = $action->handle($order);

        return redirect()->route('backend.outbound-orders.show', $order)
            ->with('success', $activated > 0
                ? "Đã kích hoạt lưu hành {$activated} tem thuộc đơn hàng \"{$order->order_number}\". Khách hàng của đại lý quét mã sẽ thấy đầy đủ thông tin sản phẩm ngay."
                : 'Đơn hàng này không có tem nào đang chờ lưu hành.');
    }

    public function cancel(OutboundOrder $order, CancelOutboundOrderAction $action): RedirectResponse
    {
        $this->authorize('update', $order);

        $action->handle($order);

        return redirect()->route('backend.outbound-orders.show', $order)
            ->with('success', 'Đã hủy đơn xuất buôn.');
    }
}
