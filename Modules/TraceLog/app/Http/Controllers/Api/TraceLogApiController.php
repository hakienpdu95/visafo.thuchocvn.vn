<?php

namespace Modules\TraceLog\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\SalesOrder\Enums\PrintLogStatus;
use Modules\SalesOrder\Models\PrintLog;
use Modules\TraceLog\Http\Resources\TraceLogListResource;
use Modules\TraceLog\Queries\GetTraceLogDetailHandler;
use Modules\TraceLog\Queries\GetTraceLogDetailQuery;
use Modules\TraceLog\Queries\ListTraceLogsHandler;
use Modules\TraceLog\Queries\ListTraceLogsQuery;
use Illuminate\Validation\Rule;

class TraceLogApiController extends Controller
{
    public function index(Request $request, ListTraceLogsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', PrintLog::class);

        $validated = $request->validate([
            'page'      => ['nullable', 'integer', 'min:1'],
            'size'      => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'    => ['nullable', 'string', 'max:300'],
            'customer'  => ['nullable', 'string', 'max:255'],
            'status'    => ['nullable', Rule::enum(PrintLogStatus::class)],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date'],
        ]);

        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $paginator = $handler->handle(new ListTraceLogsQuery(
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField: $sortField,
            sortDir: $sortDir,
            search: $validated['search'] ?? null,
            customer: $validated['customer'] ?? null,
            status: $validated['status'] ?? null,
            dateFrom: $validated['date_from'] ?? null,
            dateTo: $validated['date_to'] ?? null,
        ));

        return response()->json([
            'data'      => TraceLogListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }

    public function show(PrintLog $printLog, GetTraceLogDetailHandler $handler): JsonResponse
    {
        $this->authorize('view', $printLog);

        return response()->json($handler->handle(new GetTraceLogDetailQuery($printLog)) + [
            'can_manage' => request()->user()?->can('changeStatus', $printLog) ?? false,
        ]);
    }
}
