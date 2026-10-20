<?php

namespace Modules\TraceLog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\SalesOrder\Enums\PrintLogStatus;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Support\LabelPrintEntryFactory;
use Modules\SalesOrder\Support\BulkPrintPrefillResolver;
use Modules\SalesOrder\Support\LabelViewResolver;
use Modules\SalesOrder\Support\PrintSourceResolver;
use Modules\TraceLog\Actions\Backend\ChangeTraceLogStatusAction;
use Modules\TraceLog\Actions\Backend\ReissueTraceLogAction;
use Modules\TraceLog\Actions\Backend\UpdateTraceLogAction;
use Modules\TraceLog\Data\Requests\ChangeTraceLogStatusData;
use Modules\TraceLog\Data\Requests\UpdateTraceLogData;
use Modules\TraceLog\Queries\GetTraceLogDetailHandler;
use Modules\TraceLog\Queries\GetTraceLogDetailQuery;
use Modules\TraceLog\Queries\ListTraceLogCustomersHandler;
use Modules\TraceLog\Queries\ListTraceLogCustomersQuery;
use Modules\TraceLog\Queries\ListTraceLogsHandler;
use Modules\Vendor\Models\Vendor;
use Modules\Vendor\Queries\ListVendorOptionsHandler;
use Modules\Vendor\Queries\ListVendorOptionsQuery;

class TraceLogController extends Controller
{
    public function index(ListTraceLogCustomersHandler $customers, ListVendorOptionsHandler $vendorOptions)
    {
        $this->authorize('viewAny', PrintLog::class);

        $vendors = Vendor::query()->whereIn('id', PrintLog::query()->whereNotNull('vendor_id')->distinct()->select('vendor_id'))
            ->orderBy('name')->get(['id', 'name'])
            ->map(fn (Vendor $v) => ['value' => $v->id, 'text' => $v->name])
            ->prepend(['value' => ListTraceLogsHandler::VENDOR_NONE, 'text' => 'Nhập tay / Không rõ NCC'])
            ->values()->all();

        return view('tracelog::index', [
            'vendors'   => $vendors,
            'customers' => $customers->handle(new ListTraceLogCustomersQuery()),
            'vendorOptions' => $vendorOptions->handle(new ListVendorOptionsQuery()),
            'statuses'  => collect(PrintLogStatus::cases())
                ->map(fn (PrintLogStatus $s) => ['value' => $s->value, 'text' => $s->label()])->all(),
        ]);
    }

    /** Bản xem trước của đúng tờ tem này (nhúng vào iframe của modal) — không tự mở hộp thoại in. */
    public function preview(PrintLog $printLog, LabelViewResolver $resolver)
    {
        $this->authorize('view', $printLog);

        $printLog->load(['attributes', 'labelTemplate', 'orderItem.product', 'orderItem.salesOrder']);

        $viewPath = $resolver->forLog($printLog);
        if (! view()->exists($viewPath)) {
            $viewPath = LabelViewResolver::DEFAULT_VIEW;
        }

        return view('labels.master_print', [
            'items'     => [LabelPrintEntryFactory::make($viewPath, $printLog)],
            'autoPrint' => false,
        ]);
    }

    public function edit(PrintLog $printLog, PrintSourceResolver $sourceResolver): JsonResponse
    {
        $this->authorize('update', $printLog);

        $printLog->load(['orderItem.product', 'orderItem.salesOrder', 'productBatch.goodsReceipt.vendor', 'vendor:id,name']);
        $item = $printLog->orderItem;

        return response()->json([
            'id'                 => $printLog->id,
            'trace_code'         => $printLog->trace_code,
            'status'             => $printLog->status->value,
            'status_label'       => $printLog->status->label(),
            'product_name'       => $item?->product?->name ?? $item?->product_name_raw,
            'order_ref'          => $item?->salesOrder?->misa_ref_id,
            'weight'             => number_format((float) $printLog->weight_per_label, 3),
            'mfg_date'           => $printLog->mfg_date?->format('Y-m-d'),
            'exp_date'           => $printLog->exp_date?->format('Y-m-d'),
            'batch_code'         => $printLog->batch_code,
            'product_batch_id'   => $printLog->product_batch_id,
            'batch_text'         => $printLog->productBatch ? BulkPrintPrefillResolver::batchText($printLog->productBatch) : null,
            'vendor_id'          => $printLog->vendor_id,
            'vendor_name'        => $printLog->vendor?->name,
            'partner_product_id' => $printLog->partner_product_id,
            'supplier_name'      => $printLog->supplier_name,
            'has_batches'        => $item?->product_id !== null && ProductBatch::query()->where('product_id', $item->product_id)->exists(),
            'batches'            => $item?->product_id !== null ? $sourceResolver->batchOptions($item) : [],
            'update_url'         => route('backend.trace-logs.update', $printLog),
            'partner_products_url' => route('backend.trace-logs.partner-products', $printLog),
        ]);
    }

    public function partnerProducts(Request $request, PrintLog $printLog, PrintSourceResolver $sourceResolver): JsonResponse
    {
        $this->authorize('update', $printLog);

        $vendorId = $request->validate(['vendor_id' => ['required', 'string', 'max:26']])['vendor_id'];

        return response()->json(['data' => $printLog->orderItem ? $sourceResolver->partnerProductOptions($printLog->orderItem, $vendorId) : []]);
    }

    public function update(Request $request, PrintLog $printLog, UpdateTraceLogAction $action, GetTraceLogDetailHandler $detail): JsonResponse
    {
        $this->authorize('update', $printLog);

        $data = UpdateTraceLogData::validateAndCreate($request->all());

        $log = $action->handle($printLog, $data, $request->user()?->id);

        return response()->json([
            'message' => 'Đã cập nhật thông tin truy xuất. Mã QR giữ nguyên.',
            'detail'  => $detail->handle(new GetTraceLogDetailQuery($log->fresh())),
        ]);
    }

    /** QC đổi trạng thái tem: thu hồi / lỗi / phục hồi. */
    public function changeStatus(Request $request, PrintLog $printLog, ChangeTraceLogStatusAction $action, GetTraceLogDetailHandler $detail): JsonResponse
    {
        $this->authorize('changeStatus', $printLog);

        // Mã đã hủy đã có mã thay thế đang lưu hành → phục hồi sẽ tạo 2 mã cho cùng một tem
        if ($printLog->status === PrintLogStatus::Revoked) {
            throw ValidationException::withMessages(['status' => 'Tem đã hủy & cấp lại mã mới, không thể đổi trạng thái.']);
        }

        $data = ChangeTraceLogStatusData::validateAndCreate($request->all());
        $changed = $action->handle($printLog, $data, $request->user()?->id);

        return response()->json([
            'message' => $changed > 1
                ? "Đã cập nhật trạng thái {$changed} tem cùng lần in."
                : 'Đã cập nhật trạng thái tem.',
            'changed' => $changed,
            'detail'  => $detail->handle(new GetTraceLogDetailQuery($printLog->fresh())),
        ]);
    }

    /** Hủy mã & Cấp lại: tem mất / hỏng → hủy mã cũ, tạo tem mới cùng dữ liệu nhưng mã TXNG mới. */
    public function reissue(Request $request, PrintLog $printLog, ReissueTraceLogAction $action, GetTraceLogDetailHandler $detail): JsonResponse
    {
        $this->authorize('changeStatus', $printLog);
        $this->authorize('print', $printLog->orderItem->salesOrder);

        $data = $request->validate(
            ['reason' => ['required', 'string', 'max:200']],
            ['reason.required' => 'Vui lòng nhập lý do hủy mã (VD: tem bị rơi mất, tem rách).', 'reason.max' => 'Lý do tối đa :max ký tự.'],
        );

        $new = $action->handle($printLog, trim($data['reason']), $request->user()?->id);

        return response()->json([
            'message'   => 'Đã hủy mã ' . strtoupper($printLog->trace_code) . ' và cấp mã mới ' . strtoupper($new->trace_code) . '.',
            'print_url' => route('print.render_session', $new->print_session_id),
            'detail'    => $detail->handle(new GetTraceLogDetailQuery($new)),
        ]);
    }
}
