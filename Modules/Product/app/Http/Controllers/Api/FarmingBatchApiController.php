<?php

namespace Modules\Product\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Http\Resources\FarmingBatchListResource;
use Modules\Product\Models\FarmingBatch;

class FarmingBatchApiController extends Controller
{
    private const SORTABLE = ['batch_code', 'sowing_date', 'status', 'created_at'];

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', FarmingBatch::class);

        $validated = $request->validate([
            'page'   => ['nullable', 'integer', 'min:1'],
            'size'   => ['nullable', 'integer', 'min:5', 'max:200'],
            'search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', 'string'],
        ]);

        $query = FarmingBatch::query()->with(['vendor:id,name', 'farmingSource:id,name', 'agriSeed:id,name', 'partnerProduct:id,name']);

        $search = $validated['search'] ?? null;
        if ($search !== null && $search !== '') {
            $term = '%' . $search . '%';
            $query->where(function (Builder $sub) use ($term): void {
                $sub->where('batch_code', 'like', $term)
                    ->orWhereHas('vendor', fn (Builder $v) => $v->where('name', 'like', $term));
            });
        }

        $status = $validated['status'] ?? null;
        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortField = in_array($sortField, self::SORTABLE, true) ? $sortField : 'created_at';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortField, $sortDir);

        $perPage   = min(200, max(5, (int) ($validated['size'] ?? 50)));
        $page      = max(1, (int) ($validated['page'] ?? 1));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'      => FarmingBatchListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
