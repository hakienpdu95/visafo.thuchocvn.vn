<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\Api\DocumentMasterTypeApiController;
use Modules\Product\Http\Controllers\Api\PartnerProductApiController;
use Modules\Product\Http\Controllers\Api\ProductApiController;
use Modules\Product\Http\Controllers\DocumentMasterTypeController;
use Modules\Product\Http\Controllers\PartnerProductComplianceController;
use Modules\Product\Http\Controllers\PartnerProductController;
use Modules\Product\Http\Controllers\ProductComplianceController;
use Modules\Product\Http\Controllers\ProductController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('products', ProductController::class);

    Route::post('products/{product}/compliances', [ProductComplianceController::class, 'store'])
        ->name('products.compliances.store');
    Route::delete('products/{product}/compliances/{compliance}', [ProductComplianceController::class, 'destroy'])
        ->name('products.compliances.destroy');

    Route::resource('document-master-types', DocumentMasterTypeController::class)->except(['show']);

    Route::resource('partner-products', PartnerProductController::class);

    Route::post('partner-products/{partner_product}/compliances', [PartnerProductComplianceController::class, 'store'])
        ->name('partner-products.compliances.store');
    Route::delete('partner-products/{partner_product}/compliances/{compliance}', [PartnerProductComplianceController::class, 'destroy'])
        ->name('partner-products.compliances.destroy');
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('products', [ProductApiController::class, 'index'])->name('products');
    Route::get('document-master-types', [DocumentMasterTypeApiController::class, 'index'])->name('document-master-types');
    Route::get('partner-products', [PartnerProductApiController::class, 'index'])->name('partner-products');
});
