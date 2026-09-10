<?php

use Illuminate\Support\Facades\Route;
use Modules\Compliance\Http\Controllers\Api\ComplianceDocumentApiController;
use Modules\Compliance\Http\Controllers\Api\ComplianceWarningApiController;
use Modules\Compliance\Http\Controllers\Api\ReadinessApiController;
use Modules\Compliance\Http\Controllers\ComplianceWarningController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::get('compliance-warnings', [ComplianceWarningController::class, 'index'])->name('compliance-warnings.index');
    Route::post('compliance-warnings/{warning}/acknowledge', [ComplianceWarningController::class, 'acknowledge'])->name('compliance-warnings.acknowledge');
    Route::post('compliance-warnings/{warning}/resolve', [ComplianceWarningController::class, 'resolve'])->name('compliance-warnings.resolve');
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('compliance-warnings', [ComplianceWarningApiController::class, 'index'])->name('compliance-warnings');
    Route::get('documents', [ComplianceDocumentApiController::class, 'index'])->name('documents');
    Route::get('readiness', [ReadinessApiController::class, 'index'])->name('readiness');
});
