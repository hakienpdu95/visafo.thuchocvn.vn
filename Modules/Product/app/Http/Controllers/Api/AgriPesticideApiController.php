<?php

namespace Modules\Product\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Http\Resources\AgriPesticideListResource;
use Modules\Product\Models\AgriPesticide;

class AgriPesticideApiController extends Controller
{
    private const SORTABLE = ['trade_name', 'category', 'quarantine_days', 'created_at'];

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AgriPesticide::class);

        $validated = $request->validate([
            'page'     => ['nullable', 'integer', 'min:1'],
            'size'     => ['nullable', 'integer', 'min:5', 'max:200'],
            'search'   => ['nullable', 'string', 'max:200'],
            'category' => ['nullable', 'string'],
        ]);

        $query = AgriPesticide::query();

        $search = $validated['search'] ?? null;
        if ($search !== null && $search !== '') {
            $term = '%' . $search . '%';
            $query->where(function (Builder $sub) use ($term): void {
                $sub->where('trade_name', 'like', $term)
                    ->orWhere('active_ingredients', 'like', $term)
                    ->orWhere('target_pest', 'like', $term);
            });
        }

        $category = $validated['category'] ?? null;
        if ($category !== null && $category !== '') {
            $query->where('category', $category);
        }

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'trade_name') : 'trade_name';
        $sortField = in_array($sortField, self::SORTABLE, true) ? $sortField : 'trade_name';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortField, $sortDir);

        $perPage   = min(200, max(5, (int) ($validated['size'] ?? 50)));
        $page      = max(1, (int) ($validated['page'] ?? 1));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'      => AgriPesticideListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
