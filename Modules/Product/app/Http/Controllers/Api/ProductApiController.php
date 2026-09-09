<?php

namespace Modules\Product\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Http\Resources\ProductListResource;
use Modules\Product\Models\Product;
use Modules\Product\Queries\ListProductsHandler;
use Modules\Product\Queries\ListProductsQuery;

class ProductApiController extends Controller
{
    public function index(Request $request, ListProductsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $validated = $request->validate([
            'page'        => ['nullable', 'integer', 'min:1'],
            'size'        => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'      => ['nullable', 'string', 'max:200'],
            'category_id' => ['nullable', 'string'],
            'status'      => ['nullable', 'string'],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListProductsQuery(
            page:       max(1, (int) ($validated['page'] ?? 1)),
            perPage:    min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField:  $sortField,
            sortDir:    $sortDir,
            search:     $validated['search'] ?? null,
            categoryId: $validated['category_id'] ?? null,
            status:     $validated['status'] ?? null,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => ProductListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
