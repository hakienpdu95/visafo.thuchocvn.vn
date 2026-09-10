<?php

namespace Modules\Compliance\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Compliance\Http\Resources\ComplianceDocumentListResource;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\Compliance\Queries\ListDocumentsHandler;
use Modules\Compliance\Queries\ListDocumentsQuery;

class ComplianceDocumentApiController extends Controller
{
    public function index(Request $request, ListDocumentsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', ComplianceDocument::class);

        $validated = $request->validate([
            'page'              => ['nullable', 'integer', 'min:1'],
            'size'              => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'            => ['nullable', 'string', 'max:200'],
            'documentable_type' => ['nullable', 'string'],
            'expiring'          => ['nullable', 'boolean'],
            'expired'           => ['nullable', 'boolean'],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'expiration_date') : 'expiration_date';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListDocumentsQuery(
            page:             max(1, (int) ($validated['page'] ?? 1)),
            perPage:          min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField:        $sortField,
            sortDir:          $sortDir,
            search:           $validated['search'] ?? null,
            documentableType: $validated['documentable_type'] ?? null,
            expiring:         $request->boolean('expiring'),
            expired:          $request->boolean('expired'),
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => ComplianceDocumentListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
