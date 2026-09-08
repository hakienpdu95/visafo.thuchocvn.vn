<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\EmailVerificationController;
use Modules\Auth\Http\Controllers\MeController;
use Modules\Auth\Http\Controllers\SessionController;
use Modules\Auth\Http\Controllers\SocialAuthController;

/*
|--------------------------------------------------------------------------
| Auth Module — Web Routes
|--------------------------------------------------------------------------
| Fortify đã đăng ký sẵn: GET/POST /login, POST /logout,
| GET/POST /register, GET/POST /forgot-password, POST /reset-password.
| Fortify cũng đăng ký: PUT /user/profile-information, PUT /user/password.
|
| File này bổ sung: /home redirect, /auth/me debug, /auth/profile.
| Email verification (verification.notice/verify/send) được đăng ký thủ công
| ở đây thay cho Fortify (đã tắt Features::emailVerification() trong
| config/fortify.php) — verify KHÔNG yêu cầu auth, chỉ cần signed link hợp lệ.
*/

Route::get('/home', function () {
    return redirect('/');
})->middleware('auth')->name('home');

Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
    ->middleware('auth')
    ->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');

// Social OAuth — redirect + callback không cần auth (user chưa login)
Route::prefix('auth/social')->name('auth.social.')->group(function () {
    Route::get('{provider}',          [SocialAuthController::class, 'redirect'])->name('redirect');
    Route::get('{provider}/callback', [SocialAuthController::class, 'callback'])->name('callback');
});

// Unlink cần auth
Route::middleware('auth')->delete('auth/social/{provider}', [SocialAuthController::class, 'unlink'])
    ->name('auth.social.unlink');

Route::middleware(['auth'])->prefix('auth')->name('auth.')->group(function () {

    // Profile page — xem và cập nhật thông tin cá nhân
    Route::get('/profile', function (Request $request) {
        return view('auth::profile', [
            'user'          => $request->user()->load('socialAccounts'),
            'sessions'      => DB::table('sessions')->where('user_id', $request->user()->id)->orderByDesc('last_activity')->get(),
            'currentSessionId' => $request->session()->getId(),
        ]);
    })->name('profile');

    // Context endpoint: trả về user/org/roles của chính mình.
    // permissions chỉ hiện với System_Admin.
    Route::get('/me', MeController::class)->name('me');

    // Session/device tracking (SRS01-FR-AUTH-005 — GAP_ANALYSIS_v1.0.md §3.3 AUTH-03)
    Route::delete('/sessions/{sessionId}', [SessionController::class, 'destroy'])->name('sessions.destroy');
    Route::post('/sessions/destroy-others', [SessionController::class, 'destroyOthers'])->name('sessions.destroy-others');

});
