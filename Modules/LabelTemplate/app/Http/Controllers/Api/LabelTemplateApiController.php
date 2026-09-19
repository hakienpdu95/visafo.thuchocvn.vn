<?php

namespace Modules\LabelTemplate\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\LabelTemplate\Http\Resources\LabelTemplateListResource;
use Modules\LabelTemplate\Models\LabelTemplate;
use Modules\LabelTemplate\Queries\ListLabelTemplatesHandler;
use Modules\LabelTemplate\Queries\ListLabelTemplatesQuery;

class LabelTemplateApiController extends Controller
{
    public function index(Request $request, ListLabelTemplatesHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', LabelTemplate::class);

        $validated = $request->validate([
            'page'      => ['nullable', 'integer', 'min:1'],
            'size'      => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'    => ['nullable', 'string', 'max:200'],
            'label_size' => ['nullable', 'string', 'max:50'],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date'],
        ]);

        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'name') : 'name';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'desc' ? 'desc' : 'asc';

        $paginator = $handler->handle(new ListLabelTemplatesQuery(
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField: $sortField,
            sortDir: $sortDir,
            search: $validated['search'] ?? null,
            size: $validated['label_size'] ?? null,
            dateFrom: $validated['date_from'] ?? null,
            dateTo: $validated['date_to'] ?? null,
        ));

        return response()->json([
            'data'      => LabelTemplateListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
