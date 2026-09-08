<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * SRS01-FR-AUTH-005 (GAP_ANALYSIS_v1.0.md §3.3 AUTH-03): session/device tracking
 * chủ động — tái dùng bảng `sessions` có sẵn (SESSION_DRIVER=database), không tạo
 * bảng song song. Revoke = xóa row -> DatabaseSessionHandler không tìm thấy session
 * đó ở request kế tiếp -> tự động bị logout.
 */
class SessionController extends Controller
{
    public function destroy(Request $request, string $sessionId): RedirectResponse
    {
        if ($sessionId === $request->session()->getId()) {
            return back()->with('error', 'Không thể tự revoke session hiện tại — dùng "Đăng xuất" thay thế.');
        }

        DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->delete();

        return back()->with('success', 'Đã đăng xuất session đó.');
    }

    public function destroyOthers(Request $request): RedirectResponse
    {
        DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return back()->with('success', 'Đã đăng xuất tất cả session khác.');
    }
}
