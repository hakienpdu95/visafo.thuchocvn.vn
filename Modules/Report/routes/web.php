<?php

use Illuminate\Support\Facades\Route;
use Modules\Report\Http\Controllers\Api\ReportApiController;
use Modules\Report\Http\Controllers\ReportController;

Route::middleware(['auth', 'can:report.view'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/volume', [ReportController::class, 'volume'])->name('reports.volume');
    Route::get('reports/picking', [ReportController::class, 'picking'])->name('reports.picking');
});

Route::middleware(['auth', 'can:report.view'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('reports/volume', [ReportApiController::class, 'volume'])->name('reports.volume');
    Route::get('reports/picking', [ReportApiController::class, 'picking'])->name('reports.picking');
});
