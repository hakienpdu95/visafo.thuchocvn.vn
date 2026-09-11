<?php

use Illuminate\Support\Facades\Route;
use Modules\Compliance\Http\Controllers\ComplianceDocumentController;
use Modules\Product\Http\Controllers\Api\CategoryApiController;
use Modules\Product\Http\Controllers\Api\DocumentMasterTypeApiController;
use Modules\Product\Http\Controllers\Api\PartnerProductApiController;
use Modules\Product\Http\Controllers\Api\ProductApiController;
use Modules\Product\Http\Controllers\CategoryController;
use Modules\Product\Http\Controllers\DocumentMasterTypeController;
use Modules\Product\Http\Controllers\PartnerProductController;
use Modules\Product\Http\Controllers\ProductController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('products', ProductController::class)->except(['show']);

    Route::post('products/{product}/documents', [ComplianceDocumentController::class, 'storeForProduct'])
        ->name('products.documents.store');
    Route::delete('products/{product}/documents/{document}', [ComplianceDocumentController::class, 'destroyForProduct'])
        ->name('products.documents.destroy');

    Route::resource('document-master-types', DocumentMasterTypeController::class)->except(['show']);

    Route::resource('categories', CategoryController::class)->except(['show']);

    Route::resource('partner-products', PartnerProductController::class);

    Route::post('partner-products/{partner_product}/documents', [ComplianceDocumentController::class, 'storeForPartnerProduct'])
        ->name('partner-products.documents.store');
    Route::put('partner-products/{partner_product}/documents/{document}', [ComplianceDocumentController::class, 'updateForPartnerProduct'])
        ->name('partner-products.documents.update');
    Route::delete('partner-products/{partner_product}/documents/{document}', [ComplianceDocumentController::class, 'destroyForPartnerProduct'])
        ->name('partner-products.documents.destroy');
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('products', [ProductApiController::class, 'index'])->name('products');
    Route::get('document-master-types', [DocumentMasterTypeApiController::class, 'index'])->name('document-master-types');
    Route::get('partner-products', [PartnerProductApiController::class, 'index'])->name('partner-products');
    Route::get('categories', [CategoryApiController::class, 'index'])->name('categories');
});
