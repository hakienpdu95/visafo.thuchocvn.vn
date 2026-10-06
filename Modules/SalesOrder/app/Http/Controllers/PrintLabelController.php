<?php

namespace Modules\SalesOrder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\SalesOrder\Actions\Backend\BulkPrintSalesOrderLabelsAction;
use Modules\SalesOrder\Actions\Backend\PrintSalesOrderItemLabelAction;
use Modules\SalesOrder\Enums\PrintLogStatus;
use Modules\SalesOrder\Models\LabelPrintEvent;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\SalesOrder;
use Modules\SalesOrder\Models\SalesOrderItem;
use Modules\SalesOrder\Support\BatchAttributeResolver;
use Modules\SalesOrder\Support\BulkPrintPrefillResolver;
use Modules\SalesOrder\Support\LabelPrintEntryFactory;
use Modules\SalesOrder\Support\LabelViewResolver;
use Modules\SalesOrder\Support\PrintSourceResolver;
use Modules\GoodsReceipt\Models\ProductBatch;

class PrintLabelController extends Controller
{
    public function store(Request $request, SalesOrderItem $item, PrintSalesOrderItemLabelAction $action, PrintSourceResolver $sourceResolver): JsonResponse
    {
        $this->authorize('print', $item->order);

        // Bỏ các dòng thông tin bổ sung trống hoàn toàn (nhân viên bấm "Thêm" rồi không nhập).
        $request->merge(['extra_attributes' => collect($request->input('extra_attributes', []))
            ->filter(fn ($a) => is_array($a) && (trim((string) ($a['key'] ?? '')) !== '' || trim((string) ($a['value'] ?? '')) !== ''))
            ->map(fn ($a) => ['key' => trim((string) ($a['key'] ?? '')), 'value' => trim((string) ($a['value'] ?? ''))])
            ->values()->all()]);

        $data = $request->validate([
            'label_groups'                    => ['required', 'array', 'min:1', 'max:20'],
            'label_groups.*.weight_per_label' => ['required', 'numeric', 'gt:0', 'max:99999'],
            'label_groups.*.label_count'      => ['required', 'integer', 'min:1', 'max:200'],
            'mfg_date'         => ['nullable', 'date'],
            'exp_date'         => array_filter([
                'required', 'date',
                $request->filled('mfg_date') ? 'after_or_equal:mfg_date' : null,
            ]),
            'supplier_name'    => ['nullable', 'string', 'max:255'],
            'vendor_id'        => ['nullable', 'string', Rule::exists('vendors', 'id')->whereNull('deleted_at')],
            'product_batch_id' => ['nullable', 'string', Rule::exists('product_batches', 'id')->whereNull('deleted_at')],
            'batch_code'       => ['nullable', 'string', 'max:100'],
            'label_template_id' => ['nullable', 'string', Rule::exists('label_templates', 'id')->whereNull('deleted_at')],
            'extra_attributes'         => ['nullable', 'array', 'max:30'],
            'extra_attributes.*.key'   => ['required', 'string', 'max:100', 'distinct'],
            'extra_attributes.*.value' => ['nullable', 'string', 'max:1000'],
        ], [
            'label_groups.required'                    => 'Vui lòng thêm ít nhất 1 dòng cấu hình tem.',
            'label_groups.min'                         => 'Vui lòng thêm ít nhất 1 dòng cấu hình tem.',
            'label_groups.max'                         => 'Tối đa 20 dòng cấu hình tem.',
            'label_groups.*.weight_per_label.required' => 'Vui lòng nhập khối lượng trên mỗi tem.',
            'label_groups.*.weight_per_label.numeric'  => 'Khối lượng phải là số.',
            'label_groups.*.weight_per_label.gt'       => 'Khối lượng phải lớn hơn 0.',
            'label_groups.*.label_count.required'      => 'Vui lòng nhập số lượng tem.',
            'label_groups.*.label_count.integer'       => 'Số lượng tem phải là số nguyên.',
            'label_groups.*.label_count.min'           => 'Mỗi dòng cần in tối thiểu 1 tem.',
            'label_groups.*.label_count.max'           => 'Mỗi lần chỉ in tối đa 200 tem.',
            'mfg_date.date'             => 'NSX không hợp lệ.',
            'exp_date.required'         => 'HSD là bắt buộc để in tem.',
            'exp_date.date'             => 'HSD không hợp lệ.',
            'exp_date.after_or_equal'   => 'HSD phải sau hoặc bằng NSX.',
            'supplier_name.max'         => 'Nguồn cung không được vượt quá 255 ký tự.',
            'vendor_id.exists'          => 'Nhà cung cấp không hợp lệ.',
            'product_batch_id.exists'   => 'Lô nhập kho không hợp lệ.',
            'batch_code.max'            => 'Mã lô không được vượt quá 100 ký tự.',
            'label_template_id.exists'  => 'Mẫu tem được chọn không hợp lệ.',
            'extra_attributes.max'             => 'Tối đa 30 dòng thông tin bổ sung.',
            'extra_attributes.*.key.required'  => 'Vui lòng nhập tên thông tin cho mọi dòng bổ sung.',
            'extra_attributes.*.key.max'       => 'Tên thông tin không được vượt quá 100 ký tự.',
            'extra_attributes.*.key.distinct'  => 'Tên thông tin bổ sung không được trùng nhau.',
            'extra_attributes.*.value.max'     => 'Nội dung thông tin không được vượt quá 1000 ký tự.',
        ]);

        if (collect($data['label_groups'])->sum('label_count') > 200) {
            throw ValidationException::withMessages(['label_groups' => 'Mỗi lần chỉ in tối đa 200 tem.']);
        }

        $data = array_merge($data, $sourceResolver->resolve($data, $item));

        $result = $action->handle($item, $data, $request->user()?->id);

        return response()->json([
            'session_id'  => $result['session_id'],
            'label_count' => $result['logs']->count(),
            'reused'      => $result['reused'],
            'message'     => $result['reused']
                ? "Mặt hàng đã có {$result['logs']->count()} tem đang lưu hành — in lại đúng mã TXNG cũ, không tạo mã mới. "
                    . ($result['source_changed'] ? 'Đã cập nhật nguồn cung / lô mới cho các tem này. ' : '')
                    . 'Cần đổi khối lượng / NSX / HSD hoặc thay tem bị mất: dùng "Hủy mã & Cấp lại" ở Nhật ký TXNG.'
                : null,
            // Trang in cả phiên: mỗi tem một bản ghi + một trace_code/QR riêng.
            'print_url'   => route('print.render_session', $result['session_id']),
        ]);
    }

    /** Thông tin bổ sung (EAV) của lô hàng tương ứng — dùng điền sẵn vào modal Cấu hình In Tem. */
    public function batchAttributes(SalesOrderItem $item, BatchAttributeResolver $resolver): JsonResponse
    {
        $this->authorize('print', $item->order);

        return response()->json($resolver->forItem($item));
    }

    public function batches(SalesOrderItem $item): JsonResponse
    {
        $this->authorize('print', $item->order);

        $batches = ProductBatch::query()
            ->where('product_id', $item->product_id)
            ->with('goodsReceipt:id,misa_ref_id,receipt_date,vendor_id,supplier_name', 'goodsReceipt.vendor:id,name')
            ->latest('created_at')->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (ProductBatch $b) => [
                'value'         => $b->id,
                'text'          => BulkPrintPrefillResolver::batchText($b),
                'vendor_id'     => $b->goodsReceipt?->vendor_id,
                'vendor_name'   => $b->goodsReceipt?->vendor?->name,
                'supplier_name' => $b->goodsReceipt?->vendor_id ? null : $b->goodsReceipt?->supplier_name,
                'in_stock'      => (float) $b->current_qty > 0,
            ]);

        return response()->json(['data' => $batches]);
    }

    /** Lịch sử in của một dòng hàng (mới nhất trước). */
    public function history(SalesOrderItem $item): JsonResponse
    {
        $order = $item->order;
        $this->authorize('view', $order);
        $canPrint = Gate::allows('print', $order);

        $events = $item->printEvents()->with(['user:id,name', 'printLog'])
            ->orderByDesc('printed_at')->orderByDesc('id')->get();

        $logs = $events->groupBy('print_session_id')
            ->map(function ($group, $sessionId) use ($canPrint, $item) {
                /** @var LabelPrintEvent $first */
                $first = $group->first();
                $codes = $group->pluck('printLog')->filter();
                $firstCode = $codes->first();

                return [
                    'id'               => $sessionId,
                    'is_reprint'       => $first->is_reprint,
                    'weight_per_label' => $codes->groupBy(fn (PrintLog $log) => number_format((float) $log->weight_per_label, 3))
                        ->map(fn ($logs, $weight) => $logs->count() > 1 ? "{$weight} × {$logs->count()}" : $weight)
                        ->implode(' + '),
                    'label_count'      => (int) $group->sum('quantity'),
                    'total_weight'     => number_format((float) $codes->sum('weight_per_label'), 3),
                    'mfg_date'         => $firstCode?->mfg_date?->format('d/m/Y'),
                    'exp_date'         => $firstCode?->exp_date?->format('d/m/Y'),
                    'printed_at'       => $first->printed_at?->format('d/m/Y H:i'),
                    'printed_by'       => $first->user?->name,
                    'active_count'     => $codes->filter(fn (PrintLog $log) => $log->status->isActive())->count(),
                    'reprint_url'      => $canPrint ? route('backend.sales-orders.items.reprint', [$item, $sessionId]) : null,
                ];
            })->values();

        return response()->json(['data' => $logs, 'print_count' => $logs->count()]);
    }

    public function reprint(Request $request, SalesOrderItem $item, string $sessionId): JsonResponse
    {
        $this->authorize('print', $item->order);

        $newSessionId = DB::transaction(function () use ($item, $sessionId, $request) {
            $codes = PrintLog::query()
                ->where('order_item_id', $item->id)
                ->where('status', PrintLogStatus::Active->value)
                ->whereIn('id', LabelPrintEvent::query()->where('order_item_id', $item->id)->where('print_session_id', $sessionId)->select('print_log_id'))
                ->lockForUpdate()
                ->get();

            if ($codes->isEmpty()) {
                return null;
            }

            $newSessionId = Str::lower((string) Str::ulid());
            $codes->each(fn (PrintLog $log) => $log->markReprinted($newSessionId));
            LabelPrintEvent::record($codes, $newSessionId, $request->user()?->id, true);

            return $newSessionId;
        });

        abort_if($newSessionId === null, 404, 'Không còn tem đang lưu hành trong lần in này (đã thu hồi / hủy cấp lại).');

        return response()->json(['print_url' => route('print.render_session', $newSessionId)]);
    }

    public function storeAll(Request $request, SalesOrder $salesOrder, BulkPrintSalesOrderLabelsAction $action): JsonResponse
    {
        $this->authorize('print', $salesOrder);

        $data = $request->validate([
            'items'                                  => ['nullable', 'array', 'max:500'],
            'items.*.order_item_id'                  => ['required', 'string', 'distinct'],
            'items.*.label_groups'                   => ['required', 'array', 'min:1', 'max:20'],
            'items.*.label_groups.*.weight_per_label' => ['required', 'numeric', 'gt:0', 'max:99999'],
            'items.*.label_groups.*.label_count'      => ['required', 'integer', 'min:1', 'max:200'],
            'items.*.vendor_id'        => ['nullable', 'string', Rule::exists('vendors', 'id')->whereNull('deleted_at')],
            'items.*.supplier_name'    => ['nullable', 'string', 'max:255'],
            'items.*.product_batch_id' => ['nullable', 'string', Rule::exists('product_batches', 'id')->whereNull('deleted_at')],
            'mfg_date'         => ['nullable', 'date'],
            'exp_date'         => array_filter([
                'required', 'date',
                $request->filled('mfg_date') ? 'after_or_equal:mfg_date' : null,
            ]),
            'supplier_name'    => ['nullable', 'string', 'max:255'],
            'vendor_id'        => ['nullable', 'string', Rule::exists('vendors', 'id')->whereNull('deleted_at')],
            'batch_code'       => ['nullable', 'string', 'max:100'],
            'label_template_id' => ['required', 'string', Rule::exists('label_templates', 'id')->whereNull('deleted_at')],
        ], [
            'mfg_date.date'             => 'NSX không hợp lệ.',
            'exp_date.required'         => 'HSD là bắt buộc để in tem.',
            'exp_date.date'             => 'HSD không hợp lệ.',
            'exp_date.after_or_equal'   => 'HSD phải sau hoặc bằng NSX.',
            'supplier_name.max'         => 'Nguồn cung không được vượt quá 255 ký tự.',
            'vendor_id.exists'          => 'Nhà cung cấp không hợp lệ.',
            'batch_code.max'            => 'Mã lô không được vượt quá 100 ký tự.',
            'label_template_id.required' => 'Vui lòng chọn mẫu tem in.',
            'label_template_id.exists'  => 'Mẫu tem được chọn không hợp lệ.',
            'items.*.label_groups.required'                    => 'Mỗi mặt hàng cần ít nhất 1 dòng cấu hình tem.',
            'items.*.label_groups.min'                         => 'Mỗi mặt hàng cần ít nhất 1 dòng cấu hình tem.',
            'items.*.label_groups.max'                         => 'Mỗi mặt hàng tối đa 20 dòng cấu hình tem.',
            'items.*.label_groups.*.weight_per_label.required' => 'Vui lòng nhập khối lượng trên mỗi tem.',
            'items.*.label_groups.*.weight_per_label.numeric'  => 'Khối lượng phải là số.',
            'items.*.label_groups.*.weight_per_label.gt'       => 'Khối lượng phải lớn hơn 0.',
            'items.*.label_groups.*.label_count.required'      => 'Vui lòng nhập số lượng tem.',
            'items.*.label_groups.*.label_count.integer'       => 'Số lượng tem phải là số nguyên.',
            'items.*.label_groups.*.label_count.min'           => 'Mỗi dòng cần in tối thiểu 1 tem.',
            'items.*.label_groups.*.label_count.max'           => 'Mỗi dòng tối đa 200 tem.',
            'items.*.vendor_id.exists'        => 'Nhà cung cấp của mặt hàng không hợp lệ.',
            'items.*.supplier_name.max'       => 'Nguồn cung của mặt hàng không được vượt quá 255 ký tự.',
            'items.*.product_batch_id.exists' => 'Lô nhập kho của mặt hàng không hợp lệ.',
        ]);

        if (collect($data['items'] ?? [])->flatMap(fn ($i) => $i['label_groups'])->sum('label_count') > 2000) {
            throw ValidationException::withMessages(['items' => 'Mỗi lần in toàn bộ đơn tối đa 2000 tem.']);
        }

        $result = $action->handle($salesOrder, $data, $request->user()?->id);

        $message = $result['total'] === 0
            ? 'Không có mặt hàng nào cần in tem.'
            : "Đã in thành công {$result['printed']}/{$result['total']} mặt hàng."
                . ($result['reused'] > 0
                    ? " {$result['reused']} mặt hàng đã có tem đang lưu hành nên được in lại đúng mã TXNG cũ (không tạo mã mới)."
                    : '')
                . ($result['source_changed'] > 0
                    ? " Đã cập nhật nguồn cung / lô mới cho tem của {$result['source_changed']} mặt hàng (giữ nguyên mã TXNG)."
                    : '');

        return response()->json([
            'printed' => $result['printed'],
            'total'   => $result['total'],
            'reused'  => $result['reused'],
            'message' => $message,
            'url'     => $result['logs'] === [] ? null : route('print.render_session', $result['session_id']),
        ]);
    }

    /**
     * Render cả phiên in: mọi PrintLog cùng print_session_id (mỗi tem một bản ghi + trace_code/QR riêng).
     * Mỗi tem dùng đúng mẫu của nó; mẫu trỏ tới view không tồn tại thì dùng mẫu mặc định để không hỏng cả lượt in.
     * Chỉ đọc — không ghi log.
     */
    public function renderSession(string $sessionId, LabelViewResolver $resolver)
    {
        // Phiên gốc (print_session_id) hoặc phiên in lại gần nhất (last_print_session_id — tem cũ được dùng lại).
        // Không in tem đã thu hồi / lỗi / đã hủy cấp lại: mã đó không còn hiệu lực.
        $logs = PrintLog::query()
            ->where(fn ($q) => $q->where('print_session_id', $sessionId)->orWhere('last_print_session_id', $sessionId))
            ->where('status', PrintLogStatus::Active->value)
            ->with(['attributes', 'labelTemplate', 'orderItem.product', 'orderItem.salesOrder'])
            ->get()
            ->sortBy([
                fn (PrintLog $a, PrintLog $b) => ($a->orderItem?->line_no ?? 0) <=> ($b->orderItem?->line_no ?? 0),
                fn (PrintLog $a, PrintLog $b) => [$a->created_at, $a->id] <=> [$b->created_at, $b->id],
            ])
            ->values();

        abort_if($logs->isEmpty(), 404, 'Không còn tem đang lưu hành trong lần in này (đã thu hồi / hủy cấp lại).');

        // Một phiên chỉ thuộc một đơn hàng, nhưng vẫn kiểm quyền theo từng đơn để chắc chắn.
        $logs->map(fn (PrintLog $log) => $log->orderItem->salesOrder)->unique('id')
            ->each(fn ($order) => $this->authorize('print', $order));

        $items = $logs->map(function (PrintLog $log) use ($resolver) {
            $viewPath = $resolver->forLog($log);

            return LabelPrintEntryFactory::make(view()->exists($viewPath) ? $viewPath : LabelViewResolver::DEFAULT_VIEW, $log);
        })->all();

        return view('labels.master_print', ['items' => $items, 'autoPrint' => true]);
    }

    /**
     * Render một tem đơn lẻ (route name: print.render) — dùng view_path của mẫu tem gán cho sản phẩm.
     * Chỉ đọc — không ghi log.
     */
    public function render(PrintLog $printLog, LabelViewResolver $resolver)
    {
        $printLog->load(['attributes', 'labelTemplate', 'orderItem.product', 'orderItem.salesOrder']);

        $this->authorize('print', $printLog->orderItem->salesOrder);

        $viewPath = $resolver->forLog($printLog);

        // Tránh lỗi 500 khi mẫu tem trỏ tới file view không tồn tại.
        abort_unless(view()->exists($viewPath), 404, 'Không tìm thấy file giao diện tem in: ' . $viewPath);

        return view('labels.master_print', [
            'items'     => [LabelPrintEntryFactory::make($viewPath, $printLog)],
            'autoPrint' => true,
        ]);
    }
}
