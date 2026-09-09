<?php

namespace Modules\Product\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Http\Resources\PartnerProductListResource;
use Modules\Product\Models\PartnerProduct;
use Modules\Product\Queries\ListPartnerProductsHandler;
use Modules\Product\Queries\ListPartnerProductsQuery;

class PartnerProductApiController extends Controller
{
    public function index(Request $request, ListPartnerProductsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', PartnerProduct::class);

        $validated = $request->validate([
            'page'      => ['nullable', 'integer', 'min:1'],
            'size'      => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'    => ['nullable', 'string', 'max:200'],
            'status'    => ['nullable', 'string'],
            'vendor_id' => ['nullable', 'string'],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListPartnerProductsQuery(
            page:      max(1, (int) ($validated['page'] ?? 1)),
            perPage:   min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField: $sortField,
            sortDir:   $sortDir,
            search:    $validated['search'] ?? null,
            status:    $validated['status'] ?? null,
            vendorId:  $validated['vendor_id'] ?? null,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => PartnerProductListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
