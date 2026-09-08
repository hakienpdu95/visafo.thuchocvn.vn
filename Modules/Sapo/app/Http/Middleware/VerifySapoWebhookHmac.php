<?php

namespace Modules\Sapo\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Xác thực webhook từ Sapo bằng HMAC-SHA256 (chuẩn chính thức theo docs Sapo Admin REST API),
 * thay cho cơ chế token trong query string trước đây.
 *
 * Sapo ký RawData của request body bằng secret key (SAPO_WEBHOOK_SECRET), base64-encode kết
 * quả, gửi kèm header X-Sapo-Hmac-SHA256. Phải so khớp trên chính raw body (getContent()),
 * không phải trên dữ liệu đã qua parse/transform, nếu không chữ ký sẽ luôn sai.
 */
class VerifySapoWebhookHmac
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret    = (string) config('sapo.webhook_secret');
        $signature = (string) $request->header('X-Sapo-Hmac-SHA256');

        if ($secret === '' || $secret === 'changeme-replace-with-real-secret') {
            abort(500, 'SAPO_WEBHOOK_SECRET chưa được cấu hình.');
        }

        if ($signature === '') {
            abort(401, 'Thiếu header X-Sapo-Hmac-SHA256.');
        }

        $computed = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

        if (! hash_equals($computed, $signature)) {
            abort(401, 'Chữ ký X-Sapo-Hmac-SHA256 không hợp lệ.');
        }

        return $next($request);
    }
}
