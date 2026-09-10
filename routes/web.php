<?php

use App\Http\Controllers\Api\MediaJoditUploadController;
use App\Http\Controllers\Api\MediaUploadController;
use App\Http\Controllers\Backend\Api\DashboardChartController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\NotificationCenterController;
use App\Http\Controllers\Backend\NotificationPreferenceController;
use App\Http\Controllers\Backend\TraceabilityController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('backend.dashboard'));

/*
|--------------------------------------------------------------------------
| Media API Routes — prefix: api/v1/media
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])
    ->prefix('api/v1/media')
    ->name('api.media.')
    ->group(function () {
            // Jodit inline-image upload (orphan + uuid tracking)
        Route::post('jodit-upload',         [MediaJoditUploadController::class, 'store'])->name('jodit.upload');
        Route::delete('jodit-upload/{uuid}',[MediaJoditUploadController::class, 'destroy'])->name('jodit.destroy');
        Route::post('jodit-discard',        [MediaJoditUploadController::class, 'discard'])->name('jodit.discard');
        Route::patch('jodit-touch',         [MediaJoditUploadController::class, 'touch'])->name('jodit.touch');
        Route::get('{uuid}/url',            [MediaJoditUploadController::class, 'refreshUrl'])->name('url.refresh');

        // FilePond form-field upload (avatar, logo, thumbnail, cover, attachments)
        Route::post('upload',         [MediaUploadController::class, 'store'])->name('upload');
        Route::delete('upload/{uuid}',[MediaUploadController::class, 'destroy'])->name('upload.destroy');
    });

/*
|--------------------------------------------------------------------------
| Backend Routes — prefix: backend.*
|--------------------------------------------------------------------------
| User CRUD          → Modules/User/routes/web.php
*/
Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ── Dashboard chart API ───────────────────────────────────────────────
    // task-throughput/lead-funnel/workflow-health đã bị gỡ cùng module Task/Lead/
    // WorkflowAutomation (cleanup/remove-non-competency-modules).
    Route::prefix('api/dashboard/charts')->name('dashboard.charts.')->group(function () {
        Route::get('headcount', [DashboardChartController::class, 'headcount'])->name('headcount');
    });

    // ── Placeholder routes (modules chưa triển khai) ──────────────────
    // products.index/products.create: đã triển khai thật ở Modules/Product/routes/web.php
    // customers.*: đã triển khai thật ở Modules/Customer/routes/web.php
    // categories.*: đã triển khai thật ở Modules/Product/routes/web.php
    Route::get('/orders',           fn () => abort(503, 'Module đang phát triển'))->name('orders.index');
    Route::get('/settings',         fn () => abort(503, 'Module đang phát triển'))->name('settings.index');
    Route::get('/reports',          fn () => abort(503, 'Module đang phát triển'))->name('reports.index');
    Route::get('/document-repository', fn () => abort(503, 'Module đang phát triển'))->name('document-repository.index');
    Route::get('/readiness-check',     fn () => abort(503, 'Module đang phát triển'))->name('readiness-check.index');

    // ── Traceability Report (Farm-to-Fork) ─────────────────────────────────
    Route::get('/traceability', [TraceabilityController::class, 'index'])
        ->middleware('permission:compliance.view')
        ->name('traceability.index');

    // ── Notification Center ───────────────────────────────────────────────
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/',             [NotificationCenterController::class,    'index'])       ->name('index');
        Route::get('/preferences',  [NotificationPreferenceController::class,'index'])       ->name('preferences');
        Route::patch('/{uuid}/read',[NotificationCenterController::class,    'markRead'])    ->name('mark-read');
        Route::post('/read-all',    [NotificationCenterController::class,    'markAllRead']) ->name('read-all');
        Route::delete('/{uuid}',    [NotificationCenterController::class,    'destroy'])     ->name('destroy');
    });

});
