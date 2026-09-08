<?php

namespace Modules\Warehouse\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Warehouse\Http\Resources\SapoSyncLogListResource;
use Modules\Warehouse\Models\RetailItemTag;

class SapoSyncLogApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('warehouse.view'), 403);

        $validated = $request->validate([
            'page'   => ['nullable', 'integer', 'min:1'],
            'size'   => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
        ]);

        $query = RetailItemTag::query()
            ->whereNotNull('external_order_id')
            ->with(['batch:id,internal_batch_code', 'product:id,name,sku', 'externalOrder']);

        $search = $validated['search'] ?? null;
        if ($search !== null && $search !== '') {
            $term = '%' . $search . '%';
            $query->where(function ($sub) use ($term) {
                $sub->where('qr_code', 'like', $term)
                    ->orWhere('gs1_serial', 'like', $term)
                    ->orWhereHas('externalOrder', fn ($eo) => $eo->where('external_order_code', 'like', $term));
            });
        }

        $query->orderByDesc('sold_at');

        $perPage   = min(100, max(5, (int) ($validated['size'] ?? 25)));
        $page      = max(1, (int) ($validated['page'] ?? 1));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'      => SapoSyncLogListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
