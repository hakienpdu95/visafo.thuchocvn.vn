<?php

use Illuminate\Support\Facades\Route;
use Modules\TraceLog\Http\Controllers\Api\TraceLogApiController;
use Modules\TraceLog\Http\Controllers\TraceLogController;
use Modules\TraceLog\Http\Controllers\TraceReviewController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::get('trace-logs', [TraceLogController::class, 'index'])->name('trace-logs.index');
    Route::get('trace-logs/{print_log}/preview', [TraceLogController::class, 'preview'])->name('trace-logs.preview');
    Route::put('trace-logs/{print_log}/status', [TraceLogController::class, 'changeStatus'])->name('trace-logs.status');
    Route::post('trace-logs/{print_log}/reissue', [TraceLogController::class, 'reissue'])->name('trace-logs.reissue');

    Route::get('trace-reviews', [TraceReviewController::class, 'index'])->name('trace-reviews.index');
    Route::put('trace-reviews/{trace_review}/approve', [TraceReviewController::class, 'approve'])->name('trace-reviews.approve');
    Route::put('trace-reviews/{trace_review}/reject', [TraceReviewController::class, 'reject'])->name('trace-reviews.reject');
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('trace-logs', [TraceLogApiController::class, 'index'])->name('trace-logs');
    Route::get('trace-logs/{print_log}', [TraceLogApiController::class, 'show'])->name('trace-logs.show');
});
