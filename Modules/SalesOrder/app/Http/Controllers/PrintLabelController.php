<?php

namespace Modules\SalesOrder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\SalesOrder\Actions\Backend\BulkPrintSalesOrderLabelsAction;
use Modules\SalesOrder\Actions\Backend\PrintSalesOrderItemLabelAction;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\SalesOrder;
use Modules\SalesOrder\Models\SalesOrderItem;
use Modules\SalesOrder\Support\BatchAttributeResolver;
use Modules\SalesOrder\Support\LabelViewResolver;
use Modules\SalesOrder\Support\QrSvg;

class PrintLabelController extends Controller
{
    public function store(Request $request, SalesOrderItem $item, PrintSalesOrderItemLabelAction $action): JsonResponse
    {
        $this->authorize('print', $item->order);

        // Bỏ các dòng thông tin bổ sung trống hoàn toàn (nhân viên bấm "Thêm" rồi không nhập).
        $request->merge(['extra_attributes' => collect($request->input('extra_attributes', []))
            ->filter(fn ($a) => is_array($a) && (trim((string) ($a['key'] ?? '')) !== '' || trim((string) ($a['value'] ?? '')) !== ''))
            ->map(fn ($a) => ['key' => trim((string) ($a['key'] ?? '')), 'value' => trim((string) ($a['value'] ?? ''))])
            ->values()->all()]);

        $data = $request->validate([
            'weight_per_label' => ['required', 'numeric', 'gt:0', 'max:99999'],
            'label_count'      => ['required', 'integer', 'min:1', 'max:200'],
            'mfg_date'         => ['nullable', 'date'],
            'exp_date'         => array_filter([
                'required', 'date',
                $request->filled('mfg_date') ? 'after_or_equal:mfg_date' : null,
            ]),
            'supplier_name'    => ['nullable', 'string', 'max:255'],
            'label_template_id' => ['nullable', 'string', Rule::exists('label_templates', 'id')->whereNull('deleted_at')],
            'extra_attributes'         => ['nullable', 'array', 'max:30'],
            'extra_attributes.*.key'   => ['required', 'string', 'max:100', 'distinct'],
            'extra_attributes.*.value' => ['nullable', 'string', 'max:1000'],
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
            'label_template_id.exists'  => 'Mẫu tem được chọn không hợp lệ.',
            'extra_attributes.max'             => 'Tối đa 30 dòng thông tin bổ sung.',
            'extra_attributes.*.key.required'  => 'Vui lòng nhập tên thông tin cho mọi dòng bổ sung.',
            'extra_attributes.*.key.max'       => 'Tên thông tin không được vượt quá 100 ký tự.',
            'extra_attributes.*.key.distinct'  => 'Tên thông tin bổ sung không được trùng nhau.',
            'extra_attributes.*.value.max'     => 'Nội dung thông tin không được vượt quá 1000 ký tự.',
        ]);

        $log = $action->handle($item, $data, $request->user()?->id);

        return response()->json([
            'log_id'      => $log->id,
            'print_url'   => route('print.render', $log),
            'printed_qty' => number_format((float) $item->fresh()->printed_qty, 3),
            'printed_qty_raw' => (float) $item->fresh()->printed_qty,
        ]);
    }

    /** Thông tin bổ sung (EAV) của lô hàng tương ứng — dùng điền sẵn vào modal Cấu hình In Tem. */
    public function batchAttributes(SalesOrderItem $item, BatchAttributeResolver $resolver): JsonResponse
    {
        $this->authorize('print', $item->order);

        return response()->json($resolver->forItem($item));
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
            'reprint_url'      => $canPrint ? route('print.render', $log) : null,
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

    /**
     * Trang in ghép nhiều tem (bulk print) — chỉ đọc log đã tạo, không ghi gì thêm.
     * Mỗi mặt hàng dùng đúng mẫu tem của nó; mẫu trỏ tới view không tồn tại thì dùng mẫu mặc định để không hỏng cả lượt in.
     */
    public function labels(Request $request, SalesOrder $salesOrder, LabelViewResolver $resolver)
    {
        $this->authorize('print', $salesOrder);

        $ids = array_filter(explode(',', (string) $request->query('logs', '')));

        $logs = PrintLog::query()
            ->whereIn('id', $ids)
            ->whereHas('item', fn ($q) => $q->where('order_id', $salesOrder->id))
            ->with(['attributes', 'labelTemplate', 'orderItem.product.labelTemplate', 'orderItem.salesOrder'])
            ->orderBy('created_at')->orderBy('id')
            ->get();

        abort_if($logs->isEmpty(), 404);

        $qrSvg = QrSvg::make(route('backend.sales-orders.show', $salesOrder));

        $items = $logs->map(function (PrintLog $log) use ($resolver, $qrSvg) {
            $viewPath = $resolver->forLog($log);

            return $this->entry(view()->exists($viewPath) ? $viewPath : LabelViewResolver::DEFAULT_VIEW, $log, $qrSvg);
        })->all();

        return view('labels.master_print', ['items' => $items, 'autoPrint' => true]);
    }

    /**
     * In lại: chỉ đọc đúng bản ghi PrintLog cùng các print_log_attributes đã lưu rồi render ra tem.
     * Không nhận form cấu hình, không ghi log mới, không cộng dồn printed_qty, không sửa/xóa thông tin bổ sung.
     */
    public function reprint(PrintLog $printLog, LabelViewResolver $resolver)
    {
        return $this->render($printLog, $resolver);
    }

    public function label(PrintLog $printLog, LabelViewResolver $resolver)
    {
        return $this->render($printLog, $resolver);
    }

    /**
     * Render tem động: dùng view_path của mẫu tem gán cho sản phẩm (hoặc mẫu mặc định).
     * Chỉ đọc — không ghi log, không cộng dồn printed_qty.
     */
    public function render(PrintLog $printLog, LabelViewResolver $resolver)
    {
        $printLog->load(['attributes', 'labelTemplate', 'orderItem.product.labelTemplate', 'orderItem.salesOrder']);

        $item    = $printLog->orderItem;
        $order   = $item->salesOrder;
        $product = $item->product;

        $this->authorize('print', $order);

        $viewPath = $resolver->forLog($printLog);

        // Tránh lỗi 500 khi mẫu tem trỏ tới file view không tồn tại.
        abort_unless(view()->exists($viewPath), 404, 'Không tìm thấy file giao diện tem in: ' . $viewPath);

        return view('labels.master_print', [
            'items'     => [$this->entry($viewPath, $printLog, QrSvg::make(route('backend.sales-orders.show', $order)))],
            'autoPrint' => true,
        ]);
    }

    /** Một "mục in" cho labels.master_print: mẫu tem + dữ liệu + số tem. */
    private function entry(string $viewPath, PrintLog $log, string $qrSvg): object
    {
        return (object) [
            'viewPath'   => $viewPath,
            'item'       => $log->orderItem,
            'log'        => $log,
            'order'      => $log->orderItem->salesOrder,
            'attributes' => $log->attributes,
            'copies'     => $log->label_count,
            'qrSvg'      => $qrSvg,
        ];
    }
}
