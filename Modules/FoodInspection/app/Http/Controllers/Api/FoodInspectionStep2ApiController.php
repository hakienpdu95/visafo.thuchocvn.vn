<?php

namespace Modules\FoodInspection\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\FoodInspection\Http\Resources\FoodInspectionStep2ListResource;
use Modules\FoodInspection\Models\FoodInspectionStep2Log;
use Modules\FoodInspection\Queries\ListFoodInspectionStep2LogsHandler;
use Modules\FoodInspection\Queries\ListFoodInspectionStep2LogsQuery;

class FoodInspectionStep2ApiController extends Controller
{
    public function index(Request $request, ListFoodInspectionStep2LogsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', FoodInspectionStep2Log::class);

        $validated = $request->validate([
            'page'        => ['nullable', 'integer', 'min:1'],
            'size'        => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'      => ['nullable', 'string', 'max:200'],
            'customer_id' => ['nullable', 'string', 'max:40'],
            'result'      => ['nullable', 'in:passed,failed'],
            'date_from'   => ['nullable', 'date'],
            'date_to'     => ['nullable', 'date'],
        ]);

        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'inspection_date') : 'inspection_date';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $paginator = $handler->handle(new ListFoodInspectionStep2LogsQuery(
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField: $sortField,
            sortDir: $sortDir,
            search: $validated['search'] ?? null,
            customerId: $validated['customer_id'] ?? null,
            result: $validated['result'] ?? null,
            dateFrom: $validated['date_from'] ?? null,
            dateTo: $validated['date_to'] ?? null,
        ));

        return response()->json([
            'data'      => FoodInspectionStep2ListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
