<?php

namespace Modules\Warehouse\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Warehouse\Http\Resources\TagRollListResource;
use Modules\Warehouse\Models\TagRoll;

class TagRollApiController extends Controller
{
    private const SORTABLE = ['prefix', 'count', 'created_at'];

    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('warehouse.manage'), 403);

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortField = in_array($sortField, self::SORTABLE, true) ? $sortField : 'created_at';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = TagRoll::query()->with('creator')->orderBy($sortField, $sortDir);

        $perPage   = min(100, max(5, (int) ($validated['size'] ?? 15)));
        $page      = max(1, (int) ($validated['page'] ?? 1));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'      => TagRollListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
