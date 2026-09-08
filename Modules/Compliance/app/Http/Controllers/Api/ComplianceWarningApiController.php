<?php

namespace Modules\Compliance\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Compliance\Http\Resources\ComplianceWarningListResource;
use Modules\Compliance\Models\ComplianceWarning;
use Modules\Compliance\Queries\ListWarningsHandler;
use Modules\Compliance\Queries\ListWarningsQuery;

class ComplianceWarningApiController extends Controller
{
    public function index(Request $request, ListWarningsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', ComplianceWarning::class);

        $validated = $request->validate([
            'page'     => ['nullable', 'integer', 'min:1'],
            'size'     => ['nullable', 'integer', 'min:5', 'max:100'],
            'status'   => ['nullable', 'string'],
            'category' => ['nullable', 'string'],
            'severity' => ['nullable', 'string'],
        ]);

        $query = new ListWarningsQuery(
            page:     max(1, (int) ($validated['page'] ?? 1)),
            perPage:  min(100, max(5, (int) ($validated['size'] ?? 25))),
            status:   $validated['status'] ?? null,
            category: $validated['category'] ?? null,
            severity: $validated['severity'] ?? null,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => ComplianceWarningListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
