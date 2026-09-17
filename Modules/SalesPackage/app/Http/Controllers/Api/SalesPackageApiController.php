<?php

namespace Modules\SalesPackage\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\SalesPackage\Http\Resources\SalesPackageListResource;
use Modules\SalesPackage\Models\SalesPackage;
use Modules\SalesPackage\Queries\ListSalesPackagesHandler;
use Modules\SalesPackage\Queries\ListSalesPackagesQuery;

class SalesPackageApiController extends Controller
{
    public function index(Request $request, ListSalesPackagesHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', SalesPackage::class);

        $validated = $request->validate([
            'page'   => ['nullable', 'integer', 'min:1'],
            'size'   => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', 'string'],
        ]);

        $query = new ListSalesPackagesQuery(
            page:    max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 25))),
            search:  $validated['search'] ?? null,
            status:  $validated['status'] ?? null,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => SalesPackageListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
