<?php

namespace Modules\SalesOrder\Http\Controllers;

use App\Http\Controllers\Controller;
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
        // Mặc định chọn sẵn mẫu tem này khi sản phẩm chưa được gán mẫu riêng.
        $defaultLabelTemplateId = collect($labelTemplates)
            ->firstWhere('text', 'Tem VISAFO Rau Củ Khổ Lớn (100x75)')['value'] ?? '';

        return view('salesorder::sales-orders.show', compact('salesOrder', 'labelTemplates', 'vendors', 'defaultLabelTemplateId'));
    }
}
