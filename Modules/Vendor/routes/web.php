<?php

use Illuminate\Support\Facades\Route;
use Modules\Vendor\Http\Controllers\Api\VendorApiController;
use Modules\Vendor\Http\Controllers\VendorCertificateController;
use Modules\Vendor\Http\Controllers\VendorController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('vendors', VendorController::class);

    Route::post('vendors/{vendor}/certificates', [VendorCertificateController::class, 'store'])
        ->name('vendors.certificates.store');
    Route::delete('vendors/{vendor}/certificates/{certificate}', [VendorCertificateController::class, 'destroy'])
        ->name('vendors.certificates.destroy');
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('vendors', [VendorApiController::class, 'index'])->name('vendors');
});
