<?php

namespace Modules\TraceLog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\SalesOrder\Enums\PrintLogStatus;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Support\LabelPrintEntryFactory;
use Modules\SalesOrder\Support\LabelViewResolver;
use Modules\TraceLog\Actions\Backend\ChangeTraceLogStatusAction;
use Modules\TraceLog\Data\Requests\ChangeTraceLogStatusData;
use Modules\TraceLog\Queries\GetTraceLogDetailHandler;
use Modules\TraceLog\Queries\GetTraceLogDetailQuery;
use Modules\TraceLog\Queries\ListTraceLogCustomersHandler;
use Modules\TraceLog\Queries\ListTraceLogCustomersQuery;

class TraceLogController extends Controller
{
    public function index(ListTraceLogCustomersHandler $customers)
    {
        $this->authorize('viewAny', PrintLog::class);

        return view('tracelog::index', [
            'customers' => $customers->handle(new ListTraceLogCustomersQuery()),
            'statuses'  => collect(PrintLogStatus::cases())
                ->map(fn (PrintLogStatus $s) => ['value' => $s->value, 'text' => $s->label()])->all(),
        ]);
    }

    /** Bản xem trước của đúng tờ tem này (nhúng vào iframe của modal) — không tự mở hộp thoại in. */
    public function preview(PrintLog $printLog, LabelViewResolver $resolver)
    {
        $this->authorize('view', $printLog);

        $printLog->load(['attributes', 'labelTemplate', 'orderItem.product.labelTemplate', 'orderItem.salesOrder']);

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
}
