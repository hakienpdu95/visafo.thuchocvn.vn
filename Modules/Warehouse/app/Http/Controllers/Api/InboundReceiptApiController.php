<?php

namespace Modules\Warehouse\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Warehouse\Http\Resources\InboundReceiptListResource;
use Modules\Warehouse\Models\InboundReceipt;
use Modules\Warehouse\Queries\ListInboundReceiptsHandler;
use Modules\Warehouse\Queries\ListInboundReceiptsQuery;

class InboundReceiptApiController extends Controller
{
    public function index(Request $request, ListInboundReceiptsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', InboundReceipt::class);

        $validated = $request->validate([
            'page'   => ['nullable', 'integer', 'min:1'],
            'size'   => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', 'string'],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'received_date') : 'received_date';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListInboundReceiptsQuery(
            page:      max(1, (int) ($validated['page'] ?? 1)),
            perPage:   min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField: $sortField,
            sortDir:   $sortDir,
            search:    $validated['search'] ?? null,
            status:    $validated['status'] ?? null,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => InboundReceiptListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
