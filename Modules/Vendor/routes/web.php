<?php

use Illuminate\Support\Facades\Route;
use Modules\Compliance\Http\Controllers\ComplianceDocumentController;
use Modules\Product\Http\Controllers\VendorFarmingStepController;
use Modules\Vendor\Http\Controllers\Api\VendorApiController;
use Modules\Vendor\Http\Controllers\VendorController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('vendors', VendorController::class);

    Route::post('vendors/{vendor}/documents', [ComplianceDocumentController::class, 'storeForVendor'])
        ->name('vendors.documents.store');
    Route::put('vendors/{vendor}/documents/{document}', [ComplianceDocumentController::class, 'updateForVendor'])
        ->name('vendors.documents.update');
    Route::delete('vendors/{vendor}/documents/{document}', [ComplianceDocumentController::class, 'destroyForVendor'])
        ->name('vendors.documents.destroy');

    Route::post('vendors/{vendor}/farming-steps', [VendorFarmingStepController::class, 'store'])
        ->name('vendors.farming-steps.store');
    Route::put('vendors/{vendor}/farming-steps/{farming_step}', [VendorFarmingStepController::class, 'update'])
        ->name('vendors.farming-steps.update');
    Route::delete('vendors/{vendor}/farming-steps/{farming_step}', [VendorFarmingStepController::class, 'destroy'])
        ->name('vendors.farming-steps.destroy');
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('vendors', [VendorApiController::class, 'index'])->name('vendors');
});
