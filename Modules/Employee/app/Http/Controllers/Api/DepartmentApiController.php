<?php

namespace Modules\Employee\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Employee\Http\Resources\DepartmentListResource;
use Modules\Employee\Models\Department;
use Modules\Employee\Queries\ListDepartmentsHandler;
use Modules\Employee\Queries\ListDepartmentsQuery;

class DepartmentApiController extends Controller
{
    public function index(Request $request, ListDepartmentsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', Department::class);

        $validated = $request->validate([
            'page'         => ['nullable', 'integer', 'min:1'],
            'size'         => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'       => ['nullable', 'string', 'max:200'],
            'food_contact' => ['nullable', 'string'],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'name') : 'name';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'desc' ? 'desc' : 'asc';

        $query = new ListDepartmentsQuery(
            page:         max(1, (int) ($validated['page'] ?? 1)),
            perPage:      min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField:    $sortField,
            sortDir:      $sortDir,
            search:       $validated['search'] ?? null,
            foodContact:  $validated['food_contact'] ?? null,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => DepartmentListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
