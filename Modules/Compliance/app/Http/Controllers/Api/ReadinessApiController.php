<?php

namespace Modules\Compliance\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\Compliance\Services\ComplianceReadinessService;
use Modules\Compliance\Services\VendorReadinessResult;

class ReadinessApiController extends Controller
{
    public function index(ComplianceReadinessService $service): JsonResponse
    {
        $this->authorize('viewAny', ComplianceDocument::class);

        $data = $service->evaluateAll()->map(fn (VendorReadinessResult $result) => [
            'vendor_id'   => $result->vendor->id,
            'vendor_name' => $result->vendor->name,

            'status_value' => $result->status->value,
            'status_label' => $result->status->label(),
            'status_badge' => $result->status->badgeClass(),
            'status_emoji' => $result->status->dotEmoji(),

            'partner_product_count' => $result->partnerProductCount,

            'missing' => collect($result->missingByProduct)->map(fn (array $entry) => [
                'product_name' => $entry['partnerProduct']->name,
                'requirements' => collect($entry['missing'])->map(fn ($r) => $r->requirement->label)->values(),
            ])->values(),
        ])->values();

        return response()->json(['data' => $data]);
    }
}
