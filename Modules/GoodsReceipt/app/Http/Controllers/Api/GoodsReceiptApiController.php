<?php

namespace Modules\GoodsReceipt\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\GoodsReceipt\Http\Resources\GoodsReceiptListResource;
use Modules\GoodsReceipt\Models\GoodsReceipt;
use Modules\GoodsReceipt\Queries\ListGoodsReceiptsHandler;
use Modules\GoodsReceipt\Queries\ListGoodsReceiptsQuery;

class GoodsReceiptApiController extends Controller
{
    public function index(Request $request, ListGoodsReceiptsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', GoodsReceipt::class);

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
            'vendor_id' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListGoodsReceiptsQuery(
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField: $sortField,
            sortDir: $sortDir,
            search: $validated['search'] ?? null,
            vendorId: $validated['vendor_id'] ?? null,
            dateFrom: $validated['date_from'] ?? null,
            dateTo: $validated['date_to'] ?? null,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data' => GoodsReceiptListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }
}
