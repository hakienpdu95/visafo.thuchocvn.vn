<?php

namespace Modules\Menu\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Menu\Http\Resources\MenuListResource;
use Modules\Menu\Models\Menu;
use Modules\Menu\Queries\ListMenusHandler;
use Modules\Menu\Queries\ListMenusQuery;

class MenuApiController extends Controller
{
    public function index(Request $request, ListMenusHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', Menu::class);

        $validated = $request->validate([
            'page'        => ['nullable', 'integer', 'min:1'],
            'size'        => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'      => ['nullable', 'string', 'max:200'],
            'customer_id' => ['nullable', 'string', 'max:40'],
            'meal_time'   => ['nullable', 'in:breakfast,lunch,afternoon,dinner'],
            'date_from'   => ['nullable', 'date'],
            'date_to'     => ['nullable', 'date'],
        ]);

        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'menu_date') : 'menu_date';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $paginator = $handler->handle(new ListMenusQuery(
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField: $sortField,
            sortDir: $sortDir,
            search: $validated['search'] ?? null,
            customerId: $validated['customer_id'] ?? null,
            mealTime: $validated['meal_time'] ?? null,
            dateFrom: $validated['date_from'] ?? null,
            dateTo: $validated['date_to'] ?? null,
        ));

        return response()->json([
            'data'      => MenuListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
