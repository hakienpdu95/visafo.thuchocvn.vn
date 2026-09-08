<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\Api\BrandApiController;
use Modules\Product\Http\Controllers\Api\DocumentMasterTypeApiController;
use Modules\Product\Http\Controllers\Api\ProductApiController;
use Modules\Product\Http\Controllers\BrandController;
use Modules\Product\Http\Controllers\DocumentMasterTypeController;
use Modules\Product\Http\Controllers\ProductComplianceController;
use Modules\Product\Http\Controllers\ProductController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('products', ProductController::class);

    Route::post('products/{product}/compliances', [ProductComplianceController::class, 'store'])
        ->name('products.compliances.store');
    Route::delete('products/{product}/compliances/{compliance}', [ProductComplianceController::class, 'destroy'])
        ->name('products.compliances.destroy');

    Route::resource('brands', BrandController::class)->except(['show']);

    Route::resource('document-master-types', DocumentMasterTypeController::class)->except(['show']);
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('products', [ProductApiController::class, 'index'])->name('products');
    Route::get('brands', [BrandApiController::class, 'index'])->name('brands');
    Route::get('document-master-types', [DocumentMasterTypeApiController::class, 'index'])->name('document-master-types');
});
