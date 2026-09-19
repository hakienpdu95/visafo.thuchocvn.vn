<?php

use Illuminate\Support\Facades\Route;
use Modules\TraceLog\Http\Controllers\Api\TraceLogApiController;
use Modules\TraceLog\Http\Controllers\TraceLogController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::get('trace-logs', [TraceLogController::class, 'index'])->name('trace-logs.index');
    Route::get('trace-logs/{print_log}/preview', [TraceLogController::class, 'preview'])->name('trace-logs.preview');
    Route::put('trace-logs/{print_log}/status', [TraceLogController::class, 'changeStatus'])->name('trace-logs.status');
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('trace-logs', [TraceLogApiController::class, 'index'])->name('trace-logs');
    Route::get('trace-logs/{print_log}', [TraceLogApiController::class, 'show'])->name('trace-logs.show');
});
