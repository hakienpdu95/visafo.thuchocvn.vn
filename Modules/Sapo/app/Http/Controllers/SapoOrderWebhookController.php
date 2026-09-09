<?php

namespace Modules\Sapo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Sapo\Actions\ProcessSapoOrderWebhookAction;
use Modules\Sapo\Models\ExternalOrder;

class SapoOrderWebhookController extends Controller
{
    public function handle(Request $request, string $org_id, ProcessSapoOrderWebhookAction $action): JsonResponse
    {
        // Xác thực đã chạy ở middleware VerifySapoWebhookHmac (route sapo.hmac) — không
        // còn dùng token trong query string như trước.
        $payload = $request->all();
        $rawBody = $request->getContent();

        try {
            $order = $action->handle($payload, $rawBody);
        } catch (\Throwable $e) {
            Log::error('Sapo order webhook processing failed: ' . $e->getMessage(), ['payload' => $rawBody]);

            ExternalOrder::create([
                'external_system'     => 'sapo',
                'external_order_code' => (string) ($payload['name'] ?? $payload['id'] ?? uniqid('sapo-failed-', true)),
                'status'              => 'failed',
                'raw_payload'         => $rawBody,
            ]);

            return response()->json(['status' => 'error'], 500);
        }

        return response()->json(['status' => 'ok', 'order_id' => $order->id]);
    }
}
