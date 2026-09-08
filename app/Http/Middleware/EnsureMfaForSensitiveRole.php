<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SRS01-FR-AUTH-003 (GAP_ANALYSIS_v1.0.md §3.3 AUTH-01): MFA bắt buộc cho role
 * nhạy cảm. OPEN-IAM-03 chưa chốt danh sách đầy đủ role/action — mặc định áp
 * dụng cho super-admin/system_admin/ceo (owner đề xuất PO/Security theo register).
 * Dùng cột 2FA có sẵn của Fortify (two_factor_confirmed_at), không viết lại 2FA.
 */
class EnsureMfaForSensitiveRole
{
    /**
     * KHÔNG gồm 'super-admin' — đó là break-glass account đã bypass toàn bộ
     * Gate::before (xem AppServiceProvider), khóa cứng nó bằng middleware này tạo
     * rủi ro tự-lockout ngược với đúng mục đích break-glass (OPEN-IAM-10 chưa
     * chốt quy trình break-glass chính thức — tạm giữ super-admin luôn truy cập được).
     */
    private const SENSITIVE_ROLES = ['system_admin', 'ceo'];

    /** Loại trừ profile/2FA-setup/logout — tránh redirect loop vô hạn. */
    private const EXEMPT_ROUTE_PATTERNS = ['auth.profile', 'two-factor.*', 'logout', 'user-profile-information.*', 'user-password.*'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->hasAnyRole(self::SENSITIVE_ROLES) || $user->two_factor_confirmed_at) {
            return $next($request);
        }

        if ($request->routeIs(self::EXEMPT_ROUTE_PATTERNS)) {
            return $next($request);
        }

        return redirect()->route('auth.profile')
            ->with('error', 'Vai trò của bạn yêu cầu bật xác thực 2 yếu tố (MFA) trước khi tiếp tục (SRS01-FR-AUTH-003).');
    }
}
