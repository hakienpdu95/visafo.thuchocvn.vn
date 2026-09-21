<?php

namespace Modules\FoodInspection\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\FoodInspection\Http\Resources\FoodSampleListResource;
use Modules\FoodInspection\Models\FoodSampleLog;
use Modules\FoodInspection\Queries\ListFoodSampleLogsHandler;
use Modules\FoodInspection\Queries\ListFoodSampleLogsQuery;

class FoodSampleApiController extends Controller
{
    public function index(Request $request, ListFoodSampleLogsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', FoodSampleLog::class);

        $validated = $request->validate([
            'page'        => ['nullable', 'integer', 'min:1'],
            'size'        => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'      => ['nullable', 'string', 'max:200'],
            'customer_id' => ['nullable', 'string', 'max:40'],
            'status'      => ['nullable', 'in:stored,pending,destroyed'],
            'date_from'   => ['nullable', 'date'],
            'date_to'     => ['nullable', 'date'],
        ]);

        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'sample_date') : 'sample_date';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $paginator = $handler->handle(new ListFoodSampleLogsQuery(
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField: $sortField,
            sortDir: $sortDir,
            search: $validated['search'] ?? null,
            customerId: $validated['customer_id'] ?? null,
            status: $validated['status'] ?? null,
            dateFrom: $validated['date_from'] ?? null,
            dateTo: $validated['date_to'] ?? null,
        ));

        return response()->json([
            'data'      => FoodSampleListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
