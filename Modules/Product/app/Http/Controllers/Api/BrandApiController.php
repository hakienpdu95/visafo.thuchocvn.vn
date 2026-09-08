<?php

namespace Modules\Product\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Http\Resources\BrandListResource;
use Modules\Product\Models\Brand;

class BrandApiController extends Controller
{
    private const SORTABLE = ['name', 'products_count', 'created_at'];

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Brand::class);

        $validated = $request->validate([
            'page'   => ['nullable', 'integer', 'min:1'],
            'size'   => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'name') : 'name';
        $sortField = in_array($sortField, self::SORTABLE, true) ? $sortField : 'name';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'desc' ? 'desc' : 'asc';

        $query = Brand::query()->withCount('products')->with('media');

        $search = $validated['search'] ?? null;
        if ($search !== null && $search !== '') {
            $term = '%' . $search . '%';
            $query->where(function (Builder $sub) use ($term): void {
                $sub->where('name', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        $query->orderBy($sortField, $sortDir);
        if ($sortField !== 'id') {
            $query->orderBy('id', $sortDir);
        }

        $perPage   = min(100, max(5, (int) ($validated['size'] ?? 25)));
        $page      = max(1, (int) ($validated['page'] ?? 1));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'      => BrandListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
