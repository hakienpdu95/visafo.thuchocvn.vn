<?php

use Illuminate\Support\Facades\Route;
use Modules\SalesPackage\Http\Controllers\Api\SalesPackageApiController;
use Modules\SalesPackage\Http\Controllers\Api\SalesPackageChecklistApiController;
use Modules\SalesPackage\Http\Controllers\SalesPackageController;
use Modules\SalesPackage\Http\Controllers\SalesPackageItemController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('sales-packages', SalesPackageController::class)
        ->parameters(['sales-packages' => 'sales_package'])
        ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

    Route::get('sales-packages/{sales_package}/export', [SalesPackageController::class, 'export'])
        ->name('sales-packages.export');
    Route::post('sales-packages/{sales_package}/status', [SalesPackageController::class, 'updateStatus'])
        ->name('sales-packages.status');

    Route::put('sales-packages/{sales_package}/items/{item}', [SalesPackageItemController::class, 'update'])
        ->name('sales-packages.items.update');
    Route::delete('sales-packages/{sales_package}/items/{item}', [SalesPackageItemController::class, 'destroy'])
        ->name('sales-packages.items.destroy');
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('customers/{customer}/package-checklist', [SalesPackageChecklistApiController::class, 'show'])
        ->name('customers.package-checklist');
    Route::get('sales-packages', [SalesPackageApiController::class, 'index'])->name('sales-packages');
});
