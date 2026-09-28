<?php

use Illuminate\Support\Facades\Route;
use Modules\Contract\Http\Controllers\Api\ContractApiController;
use Modules\Contract\Http\Controllers\Api\VendorComplianceApiController;
use Modules\Contract\Http\Controllers\ContractController;
use Modules\Contract\Http\Controllers\VendorComplianceRequirementController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('contracts', ContractController::class);
    Route::resource('vendor-compliance-requirements', VendorComplianceRequirementController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['vendor-compliance-requirements' => 'group']);
    Route::delete('contracts/{contract}/media/{media}', [ContractController::class, 'destroyMedia'])->name('contracts.media.destroy');
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('contracts', [ContractApiController::class, 'index'])->name('contracts');
    Route::get('contracts/vendor-compliance', [VendorComplianceApiController::class, 'index'])->name('contracts.vendor-compliance');
});
