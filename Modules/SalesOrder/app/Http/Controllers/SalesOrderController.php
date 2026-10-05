<?php

namespace Modules\SalesOrder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\GoodsReceipt\Actions\Backend\StoreBatchQualityCheckAction;
use Modules\GoodsReceipt\Enums\QualityCheckResult;
use Modules\GoodsReceipt\Enums\QualityCheckStage;
use Modules\LabelTemplate\Models\LabelTemplate;
use Modules\LabelTemplate\Queries\ListLabelTemplateOptionsHandler;
use Modules\LabelTemplate\Queries\ListLabelTemplateOptionsQuery;
use Modules\SalesOrder\Models\SalesOrder;
use Modules\SalesOrder\Models\SalesOrderItem;
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

    public function show(SalesOrder $salesOrder, ListLabelTemplateOptionsHandler $labelTemplateOptions, ListVendorOptionsHandler $vendorOptions)
    {
        $salesOrder->load(['importedBy', 'items.product', 'items.qualityChecks']);
        $labelTemplates = $labelTemplateOptions->handle(new ListLabelTemplateOptionsQuery());
        $vendors = $vendorOptions->handle(new ListVendorOptionsQuery());
        $defaultLabelTemplateId = LabelTemplate::query()
            ->where('view_path', 'labels.templates.visafo_80x60')
            ->value('id')
            ?? LabelTemplate::query()->where('default_size', '80x60')->value('id')
            ?? '';

        return view('salesorder::sales-orders.show', compact('salesOrder', 'labelTemplates', 'vendors', 'defaultLabelTemplateId'));
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
