<?php

namespace Modules\FoodInspection\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\FoodInspection\Http\Resources\FoodInspectionStep1ListResource;
use Modules\FoodInspection\Models\FoodInspectionStep1Log;
use Modules\FoodInspection\Queries\ListFoodInspectionStep1LogsHandler;
use Modules\FoodInspection\Queries\ListFoodInspectionStep1LogsQuery;

class FoodInspectionStep1ApiController extends Controller
{
    public function index(Request $request, ListFoodInspectionStep1LogsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', FoodInspectionStep1Log::class);

        $validated = $request->validate([
            'page'         => ['nullable', 'integer', 'min:1'],
            'size'         => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'       => ['nullable', 'string', 'max:200'],
            'inspector_id' => ['nullable', 'string', 'max:40'],
            'food_group'   => ['nullable', 'in:fresh,dry'],
            'result'       => ['nullable', 'in:passed,failed'],
            'date_from'    => ['nullable', 'date'],
            'date_to'      => ['nullable', 'date'],
        ]);

        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'inspected_at') : 'inspected_at';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $paginator = $handler->handle(new ListFoodInspectionStep1LogsQuery(
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField: $sortField,
            sortDir: $sortDir,
            search: $validated['search'] ?? null,
            inspectorId: $validated['inspector_id'] ?? null,
            foodGroup: $validated['food_group'] ?? null,
            result: $validated['result'] ?? null,
            dateFrom: $validated['date_from'] ?? null,
            dateTo: $validated['date_to'] ?? null,
        ));

        return response()->json([
            'data'      => FoodInspectionStep1ListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
