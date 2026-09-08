<?php

namespace Modules\Product\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Http\Resources\DocumentMasterTypeListResource;
use Modules\Product\Models\DocumentMasterType;

class DocumentMasterTypeApiController extends Controller
{
    private const SORTABLE = ['code', 'name', 'applicable_category', 'default_validity_months', 'created_at'];

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DocumentMasterType::class);

        $validated = $request->validate([
            'page'                 => ['nullable', 'integer', 'min:1'],
            'size'                 => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'               => ['nullable', 'string', 'max:200'],
            'applicable_category'  => ['nullable', 'string'],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'applicable_category') : 'applicable_category';
        $sortField = in_array($sortField, self::SORTABLE, true) ? $sortField : 'applicable_category';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'desc' ? 'desc' : 'asc';

        $query = DocumentMasterType::query();

        $search = $validated['search'] ?? null;
        if ($search !== null && $search !== '') {
            $term = '%' . $search . '%';
            $query->where(function (Builder $sub) use ($term): void {
                $sub->where('code', 'like', $term)
                    ->orWhere('name', 'like', $term);
            });
        }

        $category = $validated['applicable_category'] ?? null;
        if ($category !== null && $category !== '') {
            $query->where('applicable_category', $category);
        }

        $query->orderBy($sortField, $sortDir);
        if ($sortField !== 'name') {
            $query->orderBy('name', 'asc');
        }

        $perPage   = min(100, max(5, (int) ($validated['size'] ?? 25)));
        $page      = max(1, (int) ($validated['page'] ?? 1));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'      => DocumentMasterTypeListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
