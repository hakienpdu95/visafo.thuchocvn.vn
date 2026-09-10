<?php

use Illuminate\Support\Facades\Route;
use Modules\Compliance\Http\Controllers\ComplianceDocumentController;
use Modules\Vendor\Http\Controllers\Api\VendorApiController;
use Modules\Vendor\Http\Controllers\VendorController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('vendors', VendorController::class);

    Route::post('vendors/{vendor}/documents', [ComplianceDocumentController::class, 'storeForVendor'])
        ->name('vendors.documents.store');
    Route::delete('vendors/{vendor}/documents/{document}', [ComplianceDocumentController::class, 'destroyForVendor'])
        ->name('vendors.documents.destroy');
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('vendors', [VendorApiController::class, 'index'])->name('vendors');
});
