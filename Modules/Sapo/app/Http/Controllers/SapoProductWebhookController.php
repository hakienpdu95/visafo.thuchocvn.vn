<?php

namespace Modules\Sapo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Sapo\Jobs\ProcessSapoProductWebhookJob;

/**
 * Hứng 3 webhook topic products/create|update|delete từ Sapo. Đã qua middleware
 * VerifySapoWebhookHmac ở tầng route — controller chỉ đẩy job và trả 200 OK ngay,
 * không mapping data trong request cycle.
 */
class SapoProductWebhookController extends Controller
{
    public function create(Request $request, string $org_id): JsonResponse
    {
        return $this->accept($request, $org_id, 'products/create');
    }

    public function update(Request $request, string $org_id): JsonResponse
    {
        return $this->accept($request, $org_id, 'products/update');
    }

    public function delete(Request $request, string $org_id): JsonResponse
    {
        return $this->accept($request, $org_id, 'products/delete');
    }

    private function accept(Request $request, string $org_id, string $topic): JsonResponse
    {
        ProcessSapoProductWebhookJob::dispatch($topic, $request->all());

        return response()->json(['status' => 'queued']);
    }
}
