<?php

namespace Modules\TraceLog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\SalesOrder\Enums\PrintLogStatus;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Support\LabelPrintEntryFactory;
use Modules\SalesOrder\Support\LabelViewResolver;
use Modules\TraceLog\Actions\Backend\ChangeTraceLogStatusAction;
use Modules\TraceLog\Actions\Backend\ReissueTraceLogAction;
use Modules\TraceLog\Data\Requests\ChangeTraceLogStatusData;
use Modules\TraceLog\Queries\GetTraceLogDetailHandler;
use Modules\TraceLog\Queries\GetTraceLogDetailQuery;
use Modules\TraceLog\Queries\ListTraceLogCustomersHandler;
use Modules\TraceLog\Queries\ListTraceLogCustomersQuery;
use Modules\TraceLog\Queries\ListTraceLogsHandler;
use Modules\Vendor\Models\Vendor;

class TraceLogController extends Controller
{
    public function index(ListTraceLogCustomersHandler $customers)
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
