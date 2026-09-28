<?php

namespace Modules\Contract\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Contract\Models\Contract;
use Modules\Contract\Queries\VendorComplianceMatrixHandler;
use Modules\Contract\Queries\VendorComplianceMatrixQuery;
use Modules\Vendor\Enums\VendorSourceGroup;
use Modules\Vendor\Enums\VendorStatus;

class VendorComplianceApiController extends Controller
{
    public function index(Request $request, VendorComplianceMatrixHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', Contract::class);

        $validated = $request->validate([
            'page'          => ['nullable', 'integer', 'min:1'],
            'size'          => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'        => ['nullable', 'string', 'max:200'],
            'source_group'  => ['nullable', Rule::enum(VendorSourceGroup::class)],
            'vendor_status' => ['nullable', Rule::in([...array_column(VendorStatus::cases(), 'value'), 'all'])],
            'state'         => ['nullable', Rule::in(['complete', 'missing', 'expiring', 'expired'])],
            'days'          => ['nullable', 'integer', Rule::in([30, 60, 90])],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'missing') : 'missing';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';
        $status    = $validated['vendor_status'] ?? VendorStatus::Active->value;

        $result = $handler->handle(new VendorComplianceMatrixQuery(
            page:         max(1, (int) ($validated['page'] ?? 1)),
            perPage:      (int) ($validated['size'] ?? 25),
            sortField:    $sortField,
            sortDir:      $sortDir,
            search:       $validated['search'] ?? null,
            sourceGroup:  $validated['source_group'] ?? null,
            vendorStatus: $status === 'all' ? null : $status,
            state:        $validated['state'] ?? null,
            warningDays:  (int) ($validated['days'] ?? 30),
        ));

        return response()->json([
            'data'      => $result['paginator']->items(),
            'last_page' => $result['paginator']->lastPage(),
            'total'     => $result['paginator']->total(),
            'summary'   => $result['summary'],
        ]);
    }
}
