<?php

namespace Modules\SalesOrder\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\SalesOrder\Http\Resources\SalesOrderListResource;
use Modules\SalesOrder\Models\SalesOrder;
use Modules\SalesOrder\Queries\ListSalesOrdersHandler;
use Modules\SalesOrder\Queries\ListSalesOrdersQuery;

class SalesOrderApiController extends Controller
{
    public function index(Request $request, ListSalesOrdersHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', SalesOrder::class);

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', 'string', 'max:30'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $paginator = $handler->handle(new ListSalesOrdersQuery(
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField: $sortField,
            sortDir: $sortDir,
            search: $validated['search'] ?? null,
            status: $validated['status'] ?? null,
            dateFrom: $validated['date_from'] ?? null,
            dateTo: $validated['date_to'] ?? null,
        ));

        return response()->json([
            'data' => SalesOrderListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }
}
