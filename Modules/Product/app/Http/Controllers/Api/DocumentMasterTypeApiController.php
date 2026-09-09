<?php

namespace Modules\Product\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Enums\DocumentGroupType;
use Modules\Product\Http\Resources\DocumentMasterTypeListResource;
use Modules\Product\Models\DocumentMasterType;

class DocumentMasterTypeApiController extends Controller
{
    private const SORTABLE = ['code', 'name', 'document_group', 'default_validity_months', 'created_at'];

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DocumentMasterType::class);

        $validated = $request->validate([
            'page'           => ['nullable', 'integer', 'min:1'],
            'size'           => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'         => ['nullable', 'string', 'max:200'],
            'document_group' => ['nullable', 'string'],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'document_group') : 'document_group';
        $sortField = in_array($sortField, self::SORTABLE, true) ? $sortField : 'document_group';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'desc' ? 'desc' : 'asc';

        $query = DocumentMasterType::query();

        $search = $validated['search'] ?? null;
        if ($search !== null && $search !== '') {
            $term = '%' . $search . '%';
            $query->where(function (Builder $sub) use ($term): void {
                $sub->where('code', 'like', $term)
                    ->orWhere('name', 'like', $term);
            });
        }

        $group = $validated['document_group'] ?? null;
        if ($group !== null && $group !== '') {
            $query->where('document_group', $group);
        }

        if ($sortField === 'document_group') {
            // Nhóm giấy tờ là danh mục cố định — sắp theo thứ tự nghiệp vụ (1→4), không theo alphabet.
            $groupOrder = collect(DocumentGroupType::cases())->map(fn ($g) => "'{$g->value}'")->implode(',');
            $query->orderByRaw("FIELD(document_group, $groupOrder) " . ($sortDir === 'desc' ? 'desc' : 'asc'));
        } else {
            $query->orderBy($sortField, $sortDir);
        }
        if ($sortField !== 'name') {
            $query->orderBy('name', 'asc');
        }

        $perPage   = min(100, max(5, (int) ($validated['size'] ?? 100)));
        $page      = max(1, (int) ($validated['page'] ?? 1));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'      => DocumentMasterTypeListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
