<?php

namespace Modules\SalesOrder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\LabelTemplate\Models\LabelTemplate;
use Modules\LabelTemplate\Queries\ListLabelTemplateOptionsHandler;
use Modules\LabelTemplate\Queries\ListLabelTemplateOptionsQuery;
use Modules\SalesOrder\Models\SalesOrder;
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
        $salesOrder->load(['importedBy', 'items.product']);
        $labelTemplates = $labelTemplateOptions->handle(new ListLabelTemplateOptionsQuery());
        $vendors = $vendorOptions->handle(new ListVendorOptionsQuery());
        $defaultLabelTemplateId = LabelTemplate::query()
            ->where('view_path', 'labels.templates.visafo_75x50')
            ->value('id')
            ?? LabelTemplate::query()->where('default_size', '75x50')->value('id')
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
}
