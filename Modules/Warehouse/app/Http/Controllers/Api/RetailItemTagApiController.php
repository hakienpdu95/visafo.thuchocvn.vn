<?php

namespace Modules\Warehouse\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Warehouse\Http\Resources\RetailItemTagListResource;
use Modules\Warehouse\Models\Batch;

class RetailItemTagApiController extends Controller
{
    private const SORT_MAP = [
        'serial'       => 'serial_number',
        'status_value' => 'status',
        'sold_at'      => 'sold_at',
    ];

    public function index(Request $request, Batch $batch): JsonResponse
    {
        $this->authorize('view', $batch);

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortKey   = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'serial') : 'serial';
        $sortField = self::SORT_MAP[$sortKey] ?? 'serial_number';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'desc' ? 'desc' : 'asc';

        $perPage   = min(100, max(5, (int) ($validated['size'] ?? 50)));
        $page      = max(1, (int) ($validated['page'] ?? 1));
        $paginator = $batch->tags()->orderBy($sortField, $sortDir)->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'      => RetailItemTagListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
