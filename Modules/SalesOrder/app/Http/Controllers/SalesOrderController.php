<?php

namespace Modules\SalesOrder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Customer\Models\Customer;
use Modules\GoodsReceipt\Actions\Backend\StoreBatchQualityCheckAction;
use Modules\GoodsReceipt\Enums\QualityCheckResult;
use Modules\GoodsReceipt\Enums\QualityCheckStage;
use Modules\LabelTemplate\Models\LabelTemplate;
use Modules\LabelTemplate\Queries\ListLabelTemplateOptionsHandler;
use Modules\LabelTemplate\Queries\ListLabelTemplateOptionsQuery;
use Modules\Product\Models\Product;
use Modules\SalesOrder\Actions\Backend\CreateSalesOrderAction;
use Modules\SalesOrder\Actions\Backend\DeleteSalesOrderAction;
use Modules\SalesOrder\Actions\Backend\UpdateSalesOrderAction;
use Modules\SalesOrder\Http\Requests\SaveSalesOrderRequest;
use Modules\SalesOrder\Enums\PrintLogStatus;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\SalesOrder;
use Modules\SalesOrder\Models\SalesOrderItem;
use Modules\SalesOrder\Support\BulkPrintPrefillResolver;
use Modules\SalesOrder\Support\SalesOrderItemRemover;
use App\Support\ManualRefIdGenerator;
use App\Support\ProductNameMismatch;
use App\Support\QuantityUnit;
use Modules\Vendor\Queries\ListVendorOptionsHandler;
use Modules\Vendor\Queries\ListVendorOptionsQuery;

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

    public function show(SalesOrder $salesOrder, ListLabelTemplateOptionsHandler $labelTemplateOptions, ListVendorOptionsHandler $vendorOptions, BulkPrintPrefillResolver $prefillResolver)
    {
        $salesOrder->load([
            'importedBy',
            'customer',
            'items' => fn ($q) => $q->when($salesOrder->trashed(), fn ($q) => $q->withTrashed())->with(['product.category', 'qualityChecks']),
        ]);
        $labelTemplates = $labelTemplateOptions->handle(new ListLabelTemplateOptionsQuery());
        $vendors = $vendorOptions->handle(new ListVendorOptionsQuery());
        $defaultLabelTemplateId = LabelTemplate::query()
            ->where('view_path', 'labels.templates.visafo_80x60')
            ->value('id')
            ?? LabelTemplate::query()->where('default_size', '80x60')->value('id')
            ?? '';
        $printPrefill = $prefillResolver->forOrder($salesOrder);

        return view('salesorder::sales-orders.show', compact('salesOrder', 'labelTemplates', 'vendors', 'defaultLabelTemplateId', 'printPrefill'));
    }

    public function create(ManualRefIdGenerator $refIds)
    {
        return view('salesorder::sales-orders.form', [
            'salesOrder' => null,
            'formData'   => $this->formData(null, $refIds->next(SalesOrder::class, SalesOrder::MANUAL_REF_PREFIX)),
        ]);
    }

    public function store(SaveSalesOrderRequest $request, CreateSalesOrderAction $action, ManualRefIdGenerator $refIds): RedirectResponse
    {
        $attributes = $request->orderAttributes();
        $attributes['misa_ref_id'] ??= $refIds->next(SalesOrder::class, SalesOrder::MANUAL_REF_PREFIX);

        $order = $action->handle([
            ...$attributes,
            'source_file_name' => null,
            'imported_by'      => $request->user()?->id,
        ], $request->orderItems());

        return redirect()->route('backend.sales-orders.show', $order)
            ->with('success', 'Đã tạo đơn xuất hàng ' . $order->misa_ref_id . '.');
    }

    public function edit(SalesOrder $salesOrder)
    {
        if ($reason = $salesOrder->lockReason()) {
            return redirect()->route('backend.sales-orders.show', $salesOrder)
                ->with('error', $reason . ' Không thể sửa đơn.');
        }

        $salesOrder->load(['customer', 'items.product']);

        return view('salesorder::sales-orders.form', [
            'salesOrder' => $salesOrder,
            'formData'   => $this->formData($salesOrder, $salesOrder->misa_ref_id),
        ]);
    }

    public function update(SaveSalesOrderRequest $request, SalesOrder $salesOrder, UpdateSalesOrderAction $action): RedirectResponse
    {
        $attributes = $request->orderAttributes();
        if (! $salesOrder->isManual() || $salesOrder->hasPrintHistory() || $attributes['misa_ref_id'] === null) {
            $attributes['misa_ref_id'] = $salesOrder->misa_ref_id;
        }

        $action->handle($salesOrder, $attributes, $request->orderItems(), $request->user()?->id);

        return redirect()->route('backend.sales-orders.show', $salesOrder)
            ->with('success', 'Đã cập nhật đơn xuất hàng ' . $attributes['misa_ref_id'] . '.');
    }

    public function destroy(SalesOrder $salesOrder, DeleteSalesOrderAction $action): RedirectResponse
    {
        $action->handle($salesOrder, request()->user()?->id);

        return redirect()->route('backend.sales-orders.index')
            ->with('success', 'Đã xóa đơn xuất hàng ' . $salesOrder->misa_ref_id . '.');
    }

    /** @return array<string, mixed> */
    private function formData(?SalesOrder $order, string $defaultRefId): array
    {
        $oldItems = old('items');
        $meta = $this->itemMeta($order);

        if (is_array($oldItems)) {
            $products = Product::query()
                ->whereIn('id', collect($oldItems)->pluck('product_id')->filter()->all())
                ->get(['id', 'sku', 'name', 'unit'])
                ->keyBy('id');

            $rows = collect($oldItems)->map(fn ($row, $key) => [
                'uid'           => (string) $key,
                'product'       => ($p = $products->get($row['product_id'] ?? null)) ? $this->productOption($p) : null,
                'requested_qty' => $row['requested_qty'] ?? '',
                ...($meta[(string) $key] ?? []),
            ])->values()->all();
        } else {
            $rows = ($order?->items ?? collect())->sortBy('line_no')->map(fn ($item) => [
                'uid'           => (string) $item->id,
                'product'       => $item->product ? $this->productOption($item->product) : null,
                'requested_qty' => rtrim(rtrim((string) $item->requested_qty, '0'), '.'),
                ...($meta[$item->id] ?? []),
            ])->values()->all();
        }

        $customerId = old('customer_id', $order?->customer_id);
        $customer = $customerId
            ? ($order?->customer?->id === $customerId ? $order->customer : Customer::query()->find($customerId))
            : null;

        return [
            'fields' => [
                'misa_ref_id'      => old('misa_ref_id', $defaultRefId),
                'customer_id'      => $customer?->id ?? '',
                'customer_name'    => old('customer_name', $order?->customer_name ?? ''),
                'delivery_address' => old('delivery_address', $order?->delivery_address ?? ''),
                'delivery_date'    => old('delivery_date', $order?->delivery_date?->toDateString() ?? now()->addDay()->toDateString()),
            ],
            'customer' => $customer ? ['id' => $customer->id, 'text' => $customer->name, 'code' => $customer->customer_code, 'address' => $customer->address] : null,
            'rows'     => $rows,
            'errors'   => session('errors')?->getBag('default')->getMessages() ?? [],
            'urls'     => [
                'products'       => route('backend.api.sales-orders.product-options'),
                'customers'      => route('backend.api.sales-orders.customer-options'),
                'deliveryPoints' => route('backend.api.sales-orders.delivery-point-options', ['customer' => '__ID__']),
            ],
        ];
    }

    /** @return array<string, array{orig_product_id: string, orig_qty: string, printed: int, qc: bool, raw_name: ?string}> */
    private function itemMeta(?SalesOrder $order): array
    {
        if ($order === null) {
            return [];
        }

        $items = $order->items()->with('product:id,name')->get(['id', 'product_id', 'product_name_raw', 'requested_qty']);
        $ids = $items->pluck('id')->all();
        $printed = PrintLog::query()->whereIn('order_item_id', $ids)->where('status', PrintLogStatus::Active->value)
            ->selectRaw('order_item_id, COUNT(*) as total')->groupBy('order_item_id')->pluck('total', 'order_item_id');
        $qc = SalesOrderItemRemover::qcItemIds($ids);

        return $items->mapWithKeys(fn (SalesOrderItem $item) => [$item->id => [
            'orig_product_id' => $item->product_id,
            'orig_qty'        => rtrim(rtrim((string) $item->requested_qty, '0'), '.'),
            'printed'         => (int) ($printed[$item->id] ?? 0),
            'qc'              => in_array($item->id, $qc, true),
            'raw_name'        => $item->product && ProductNameMismatch::isSuspicious($item->product->name, $item->product_name_raw) ? $item->product_name_raw : null,
        ]])->all();
    }

    /** @return array<string, mixed> */
    private function productOption(Product $product): array
    {
        return [
            'id'      => $product->id,
            'text'    => $product->name,
            'sku'     => $product->sku,
            'unit'    => $product->unit,
            'decimal' => QuantityUnit::allowsDecimal($product->unit),
        ];
    }

    public function updateDeliveryDate(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $this->authorize('print', $salesOrder);

        $data = $request->validate([
            'delivery_date' => ['required', 'date'],
        ], [
            'delivery_date.required' => 'Vui lòng chọn ngày giao hàng.',
            'delivery_date.date'     => 'Ngày giao hàng không hợp lệ.',
        ]);

        $salesOrder->update(['delivery_date' => $data['delivery_date']]);

        return back()->with('success', 'Đã cập nhật ngày giao hàng.');
    }

    /** Xuất kho: ghi thời điểm xuất và sinh mã vận đơn (GH-yymmdd-XXXX). */
    public function ship(SalesOrder $salesOrder): RedirectResponse
    {
        $this->authorize('print', $salesOrder);

        if ($salesOrder->shipped_at !== null) {
            return back()->with('error', 'Đơn này đã xuất kho lúc ' . $salesOrder->shipped_at->format('H:i d/m/Y') . '.');
        }

        do {
            $code = 'GH-' . now()->format('ymd') . '-' . Str::upper(Str::random(4));
        } while (SalesOrder::withTrashed()->where('delivery_code', $code)->exists());

        $salesOrder->update([
            'delivery_code' => $code,
            'shipped_at'    => now(),
            'status'        => SalesOrder::STATUS_SHIPPING,
        ]);

        return back()->with('success', 'Đã xuất kho — mã vận đơn ' . $code . '.');
    }

    /** Xác nhận giao thành công. */
    public function deliver(SalesOrder $salesOrder): RedirectResponse
    {
        $this->authorize('print', $salesOrder);

        if ($salesOrder->shipped_at === null) {
            return back()->with('error', 'Cần bấm "Xuất kho" trước khi xác nhận đã giao.');
        }
        if ($salesOrder->delivered_at !== null) {
            return back()->with('error', 'Đơn này đã được xác nhận giao.');
        }

        $salesOrder->update([
            'delivered_at' => now(),
            'status'       => SalesOrder::STATUS_DELIVERED,
        ]);

        return back()->with('success', 'Đã xác nhận giao hàng thành công.');
    }

    /** QC trước xuất cho một dòng hàng. */
    public function storeItemQualityCheck(Request $request, SalesOrderItem $item, StoreBatchQualityCheckAction $action): RedirectResponse
    {
        $this->authorize('print', $item->salesOrder);

        $data = $request->validate([
            'result'     => ['required', Rule::enum(QualityCheckResult::class)],
            'checked_at' => ['nullable', 'date', 'before_or_equal:now'],
            'note'       => ['nullable', 'string', 'max:500'],
        ], [
            'result.required'            => 'Vui lòng chọn kết quả.',
            'checked_at.before_or_equal' => 'Thời điểm kiểm tra không được ở tương lai.',
        ]);

        $check = $action->handle(array_merge($data, [
            'sales_order_item_id' => $item->id,
            'stage'               => QualityCheckStage::PreDispatch->value,
        ]));

        return back()->with('success', 'Đã ghi QC trước xuất cho "' . ($item->product?->name ?? $item->product_name_raw) . '": ' . $check->result->label() . '.');
    }
}
