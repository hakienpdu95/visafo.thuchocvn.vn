<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Exempt payment gateway webhooks from CSRF — they are server-to-server calls
        $middleware->validateCsrfTokens(except: [
            'billing/webhook/*',
        ]);
        $middleware->alias([
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'sapo.hmac'          => \Modules\Sapo\Http\Middleware\VerifySapoWebhookHmac::class,
        ]);
        // InjectRequestId phải chạy đầu tiên để tất cả request đều có X-Request-Id
        $middleware->prepend(\App\Http\Middleware\RemoveServerHeaders::class);
        $middleware->prepend(\Modules\ActivityLog\Http\Middleware\InjectRequestId::class);
        $middleware->appendToGroup('web', \Modules\ActivityLog\Http\Middleware\CaptureHttpContext::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\SecurityHeaders::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureUserIsActive::class);
        // SRS01-FR-AUTH-003 (GAP_ANALYSIS_v1.0.md §3.3 AUTH-01) — MFA bắt buộc cho role nhạy cảm
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureMfaForSensitiveRole::class);
        // EnsureFrontendRequestsAreStateful phải đứng đầu api group để auth:sanctum
        // có thể dùng session cookie từ browser (SPA/Tabulator AJAX calls).
        // Không có middleware này, sanctum chỉ nhận Bearer token → 401.
        $middleware->prependToGroup('api', \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class);
        $middleware->appendToGroup('api', \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api');
        $middleware->appendToGroup('api', \Modules\ActivityLog\Http\Middleware\CaptureHttpContext::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
