<?php

namespace Modules\Product\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Http\Resources\AgriFertilizerListResource;
use Modules\Product\Models\AgriFertilizer;

class AgriFertilizerApiController extends Controller
{
    private const SORTABLE = ['name', 'category', 'created_at'];

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AgriFertilizer::class);

        $validated = $request->validate([
            'page'     => ['nullable', 'integer', 'min:1'],
            'size'     => ['nullable', 'integer', 'min:5', 'max:200'],
            'search'   => ['nullable', 'string', 'max:200'],
            'category' => ['nullable', 'string'],
        ]);

        $query = AgriFertilizer::query();

        $search = $validated['search'] ?? null;
        if ($search !== null && $search !== '') {
            $term = '%' . $search . '%';
            $query->where(function (Builder $sub) use ($term): void {
                $sub->where('name', 'like', $term)
                    ->orWhere('ingredients', 'like', $term)
                    ->orWhere('applicant', 'like', $term);
            });
        }

        $category = $validated['category'] ?? null;
        if ($category !== null && $category !== '') {
            $query->where('category', $category);
        }

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'name') : 'name';
        $sortField = in_array($sortField, self::SORTABLE, true) ? $sortField : 'name';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortField, $sortDir);

        $perPage   = min(200, max(5, (int) ($validated['size'] ?? 50)));
        $page      = max(1, (int) ($validated['page'] ?? 1));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'      => AgriFertilizerListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
