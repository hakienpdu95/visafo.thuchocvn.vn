<?php

namespace Modules\Customer\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Customer\Http\Resources\CustomerListResource;
use Modules\Customer\Models\Customer;
use Modules\Customer\Queries\ListCustomersHandler;
use Modules\Customer\Queries\ListCustomersQuery;

class CustomerApiController extends Controller
{
    public function index(Request $request, ListCustomersHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $validated = $request->validate([
            'page'           => ['nullable', 'integer', 'min:1'],
            'size'           => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'         => ['nullable', 'string', 'max:200'],
            'customer_group' => ['nullable', 'string'],
            'meal_model'     => ['nullable', 'string'],
            'status'         => ['nullable', 'string'],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListCustomersQuery(
            page:          max(1, (int) ($validated['page'] ?? 1)),
            perPage:       min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField:     $sortField,
            sortDir:       $sortDir,
            search:        $validated['search'] ?? null,
            customerGroup: $validated['customer_group'] ?? null,
            mealModel:     $validated['meal_model'] ?? null,
            status:        $validated['status'] ?? null,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => CustomerListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
