<?php

namespace Modules\HanoiCheckOrder\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\HanoiCheckOrder\Enums\OrderSyncStatus;
use Modules\HanoiCheckOrder\Http\Resources\HanoiCheckOrderListResource;
use Modules\HanoiCheckOrder\Models\HanoiCheckOrderLink;
use Modules\HanoiCheckOrder\Queries\ListHanoiCheckOrdersHandler;
use Modules\HanoiCheckOrder\Queries\ListHanoiCheckOrdersQuery;
use Modules\SalesOrder\Models\SalesOrder;

class HanoiCheckOrderApiController extends Controller
{
    public function index(Request $request, ListHanoiCheckOrdersHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', HanoiCheckOrderLink::class);

        $validated = $request->validate([
            'page'      => ['nullable', 'integer', 'min:1'],
            'size'      => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'    => ['nullable', 'string', 'max:255'],
            'link'      => ['nullable', Rule::in([ListHanoiCheckOrdersQuery::LINK_LINKED, ListHanoiCheckOrdersQuery::LINK_UNLINKED])],
            'status'    => ['nullable', Rule::in(array_keys(SalesOrder::statusLabels()))],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date'],
            'sync'      => ['nullable', Rule::enum(OrderSyncStatus::class)],
        ]);

        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'delivery_date') : 'delivery_date';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $paginator = $handler->handle(new ListHanoiCheckOrdersQuery(
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField: $sortField,
            sortDir: $sortDir,
            search: $validated['search'] ?? null,
            link: $validated['link'] ?? null,
            status: $validated['status'] ?? null,
            dateFrom: $validated['date_from'] ?? null,
            dateTo: $validated['date_to'] ?? null,
            syncStatus: $validated['sync'] ?? null,
        ));

        return response()->json([
            'data'      => HanoiCheckOrderListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
