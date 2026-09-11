<?php

namespace Modules\Auth\Http\Responses;

use App\Enums\RoleEnum;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): mixed
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $homeRoute = $request->user()?->hasRole(RoleEnum::FARMER->value)
            ? route('farmer.dashboard')
            : route('backend.dashboard');

        $redirect = redirect()->intended($homeRoute);

        // Lưu preference "ghi nhớ đăng nhập" vào cookie riêng (1 năm).
        // Cookie này CHỈ nhớ ý định của user (để pre-check checkbox sau logout),
        // khác với remember token của Laravel dùng để tự động đăng nhập lại.
        if ($request->boolean('remember')) {
            $redirect->withCookie(cookie('pref_remember', '1', 60 * 24 * 365));
        } else {
            $redirect->withCookie(cookie()->forget('pref_remember'));
        }

        return $redirect;
    }
}
