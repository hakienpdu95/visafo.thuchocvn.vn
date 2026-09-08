<?php

namespace Modules\Warehouse\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Sapo\Models\SapoProductSyncLog;
use Modules\Warehouse\Http\Resources\SapoProductSyncLogListResource;

class SapoProductSyncLogApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('warehouse.view'), 403);

        $validated = $request->validate([
            'page'   => ['nullable', 'integer', 'min:1'],
            'size'   => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', 'string', 'in:success,failed'],
        ]);

        $query = SapoProductSyncLog::query();

        $search = $validated['search'] ?? null;
        if ($search !== null && $search !== '') {
            $term = '%' . $search . '%';
            $query->where(function ($sub) use ($term) {
                $sub->where('sku', 'like', $term)
                    ->orWhere('product_name', 'like', $term)
                    ->orWhere('sapo_product_id', 'like', $term);
            });
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $query->orderByDesc('created_at');

        $perPage   = min(100, max(5, (int) ($validated['size'] ?? 25)));
        $page      = max(1, (int) ($validated['page'] ?? 1));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'      => SapoProductSyncLogListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
