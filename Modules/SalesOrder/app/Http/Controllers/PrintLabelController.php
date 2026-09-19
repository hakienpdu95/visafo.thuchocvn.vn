<?php

namespace Modules\SalesOrder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Modules\SalesOrder\Actions\Backend\BulkPrintSalesOrderLabelsAction;
use Modules\SalesOrder\Actions\Backend\PrintSalesOrderItemLabelAction;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\SalesOrder;
use Modules\SalesOrder\Models\SalesOrderItem;
use Modules\SalesOrder\Support\QrSvg;

class PrintLabelController extends Controller
{
    public function store(Request $request, SalesOrderItem $item, PrintSalesOrderItemLabelAction $action): JsonResponse
    {
        $this->authorize('print', $item->order);

        $data = $request->validate([
            'weight_per_label' => ['required', 'numeric', 'gt:0', 'max:99999'],
            'label_count'      => ['required', 'integer', 'min:1', 'max:200'],
            'mfg_date'         => ['nullable', 'date'],
            'exp_date'         => array_filter([
                'required', 'date',
                $request->filled('mfg_date') ? 'after_or_equal:mfg_date' : null,
            ]),
            'supplier_name'    => ['nullable', 'string', 'max:255'],
        ], [
            'weight_per_label.required' => 'Vui lòng nhập khối lượng trên mỗi tem.',
            'weight_per_label.numeric'  => 'Khối lượng phải là số.',
            'weight_per_label.gt'       => 'Khối lượng phải lớn hơn 0.',
            'label_count.required'      => 'Vui lòng nhập số lượng tem.',
            'label_count.integer'       => 'Số lượng tem phải là số nguyên.',
            'label_count.min'           => 'Cần in tối thiểu 1 tem.',
            'label_count.max'           => 'Mỗi lần chỉ in tối đa 200 tem.',
            'mfg_date.date'             => 'NSX không hợp lệ.',
            'exp_date.required'         => 'HSD là bắt buộc để in tem.',
            'exp_date.date'             => 'HSD không hợp lệ.',
            'exp_date.after_or_equal'   => 'HSD phải sau hoặc bằng NSX.',
            'supplier_name.max'         => 'Nguồn cung không được vượt quá 255 ký tự.',
        ]);

        $log = $action->handle($item, $data, $request->user()?->id);

        return response()->json([
            'log_id'      => $log->id,
            'url'         => route('backend.sales-orders.print-logs.label', $log),
            'printed_qty' => number_format((float) $item->fresh()->printed_qty, 3),
            'printed_qty_raw' => (float) $item->fresh()->printed_qty,
        ]);
    }

    /** Lịch sử in của một dòng hàng (mới nhất trước). */
    public function history(SalesOrderItem $item): JsonResponse
    {
        $order = $item->order;
        $this->authorize('view', $order);
        $canPrint = Gate::allows('print', $order);

        $logs = $item->printLogs()->with('printedBy:id,name')->latest()->get()->map(fn (PrintLog $log) => [
            'id'               => $log->id,
            'weight_per_label' => number_format((float) $log->weight_per_label, 3),
            'label_count'      => $log->label_count,
            'total_weight'     => number_format((float) $log->weight_per_label * $log->label_count, 3),
            'mfg_date'         => $log->mfg_date?->format('d/m/Y'),
            'exp_date'         => $log->exp_date?->format('d/m/Y'),
            'printed_at'       => $log->created_at?->format('d/m/Y H:i'),
            'printed_by'       => $log->printedBy?->name,
            // In lại: chỉ mở lại tem cũ, không ghi log mới, không cộng dồn printed_qty.
            'reprint_url'      => $canPrint ? route('backend.sales-orders.print-logs.label', $log) : null,
        ]);

        return response()->json(['data' => $logs]);
    }

    /** In tem toàn bộ đơn: các dòng còn thiếu và đã cấu hình shelf_life_days. */
    public function storeAll(Request $request, SalesOrder $salesOrder, BulkPrintSalesOrderLabelsAction $action): JsonResponse
    {
        $this->authorize('print', $salesOrder);

        $result = $action->handle($salesOrder, $request->user()?->id);

        $message = $result['total'] === 0
            ? 'Không có mặt hàng nào cần in tem.'
            : "Đã in thành công {$result['printed']}/{$result['total']} mặt hàng."
                . ($result['manual'] > 0 ? " Có {$result['manual']} mặt hàng cần khai báo HSD thủ công." : '');

        return response()->json([
            'printed' => $result['printed'],
            'total'   => $result['total'],
            'manual'  => $result['manual'],
            'message' => $message,
            'items'   => $result['items'],
            'url'     => $result['logs'] === []
                ? null
                : route('backend.sales-orders.labels', [
                    'sales_order' => $salesOrder,
                    'logs'        => implode(',', array_map(fn (PrintLog $l) => $l->id, $result['logs'])),
                ]),
        ]);
    }

    /** Trang in ghép nhiều tem (bulk print) — chỉ đọc log đã tạo, không ghi gì thêm. */
    public function labels(Request $request, SalesOrder $salesOrder)
    {
        $this->authorize('print', $salesOrder);

        $ids = array_filter(explode(',', (string) $request->query('logs', '')));

        $logs = PrintLog::query()
            ->whereIn('id', $ids)
            ->whereHas('item', fn ($q) => $q->where('order_id', $salesOrder->id))
            ->with('item.product')
            ->orderBy('created_at')->orderBy('id')
            ->get();

        abort_if($logs->isEmpty(), 404);

        return $this->renderLabels($salesOrder, $logs);
    }

    public function label(PrintLog $printLog)
    {
        $printLog->load(['item.product', 'item.order']);
        $item = $printLog->item;
        $order = $item->order;

        $this->authorize('print', $order);

        return $this->renderLabels($order, collect([$printLog]));
    }

    private function renderLabels(SalesOrder $order, $logs)
    {
        // Chưa có trang truy xuất công khai → QR trỏ về đơn xuất hàng nguồn.
        return view('salesorder::print.label', [
            'order'  => $order,
            'logs'   => $logs,
            'qrSvg'  => QrSvg::make(route('backend.sales-orders.show', $order)),
        ]);
    }
}
