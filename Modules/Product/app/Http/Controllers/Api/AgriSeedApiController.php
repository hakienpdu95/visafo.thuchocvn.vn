<?php

namespace Modules\Product\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Http\Resources\AgriSeedListResource;
use Modules\Product\Models\AgriSeed;

class AgriSeedApiController extends Controller
{
    private const SORTABLE = ['name', 'crop_type', 'created_at'];

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AgriSeed::class);

        $validated = $request->validate([
            'page'      => ['nullable', 'integer', 'min:1'],
            'size'      => ['nullable', 'integer', 'min:5', 'max:200'],
            'search'    => ['nullable', 'string', 'max:200'],
            'crop_type' => ['nullable', 'string'],
        ]);

        $query = AgriSeed::query();

        $search = $validated['search'] ?? null;
        if ($search !== null && $search !== '') {
            $term = '%' . $search . '%';
            $query->where(function (Builder $sub) use ($term): void {
                $sub->where('name', 'like', $term)
                    ->orWhere('author_applicant', 'like', $term)
                    ->orWhere('decision_number', 'like', $term);
            });
        }

        $cropType = $validated['crop_type'] ?? null;
        if ($cropType !== null && $cropType !== '') {
            $query->where('crop_type', $cropType);
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
            'data'      => AgriSeedListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
