<?php

use Illuminate\Support\Facades\Route;
use Modules\Compliance\Http\Controllers\Api\ComplianceDocumentApiController;
use Modules\Compliance\Http\Controllers\ComplianceDocumentController;
use Modules\Compliance\Http\Controllers\InternalFacilityController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::get('internal-compliance', [InternalFacilityController::class, 'index'])->name('internal-compliance.index');
    Route::get('internal-compliance/export', [InternalFacilityController::class, 'export'])->name('internal-compliance.export');
    Route::post('internal-facilities', [InternalFacilityController::class, 'store'])->name('internal-facilities.store');
    Route::put('internal-facilities/{internal_facility}', [InternalFacilityController::class, 'update'])->name('internal-facilities.update');

    Route::post('internal-facilities/{internal_facility}/documents', [ComplianceDocumentController::class, 'storeForInternalFacility'])
        ->name('internal-facilities.documents.store');
    Route::put('internal-facilities/{internal_facility}/documents/{document}', [ComplianceDocumentController::class, 'updateForInternalFacility'])
        ->name('internal-facilities.documents.update');
    Route::delete('internal-facilities/{internal_facility}/documents/{document}', [ComplianceDocumentController::class, 'destroyForInternalFacility'])
        ->name('internal-facilities.documents.destroy');
    Route::delete('internal-facilities/{internal_facility}/documents/{document}/media/{media}', [ComplianceDocumentController::class, 'destroyMediaForInternalFacility'])
        ->name('internal-facilities.documents.media.destroy');
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('documents', [ComplianceDocumentApiController::class, 'index'])->name('documents');
});
