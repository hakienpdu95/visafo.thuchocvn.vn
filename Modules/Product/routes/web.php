<?php

use Illuminate\Support\Facades\Route;
use Modules\Compliance\Http\Controllers\ComplianceDocumentController;
use Modules\Product\Http\Controllers\Api\AgriFertilizerApiController;
use Modules\Product\Http\Controllers\Api\AgriPesticideApiController;
use Modules\Product\Http\Controllers\Api\AgriSeedApiController;
use Modules\Product\Http\Controllers\Api\CategoryApiController;
use Modules\Product\Http\Controllers\Api\DocumentMasterTypeApiController;
use Modules\Product\Http\Controllers\Api\FarmingBatchApiController;
use Modules\Product\Http\Controllers\Api\FarmingSourceApiController;
use Modules\Product\Http\Controllers\Api\PartnerProductApiController;
use Modules\Product\Http\Controllers\Api\ProductApiController;
use Modules\Product\Http\Controllers\AgriFertilizerController;
use Modules\Product\Http\Controllers\AgriPesticideController;
use Modules\Product\Http\Controllers\AgriSeedController;
use Modules\Product\Http\Controllers\CategoryController;
use Modules\Product\Http\Controllers\DocumentMasterTypeController;
use Modules\Product\Http\Controllers\Farmer\FarmerDashboardController;
use Modules\Product\Http\Controllers\Farmer\FarmingLogController;
use Modules\Product\Http\Controllers\FarmingBatchController;
use Modules\Product\Http\Controllers\FarmingSourceController;
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

    Route::prefix('master-data/pesticides')->name('master-data.pesticides.')->group(function () {
        Route::get('/', [AgriPesticideController::class, 'index'])->name('index');
        Route::get('{agri_pesticide}/edit', [AgriPesticideController::class, 'edit'])->name('edit');
        Route::put('{agri_pesticide}', [AgriPesticideController::class, 'update'])->name('update');
    });

    Route::get('master-data/fertilizers', [AgriFertilizerController::class, 'index'])
        ->name('master-data.fertilizers.index');

    Route::prefix('master-data/seeds')->name('master-data.seeds.')->group(function () {
        Route::get('/', [AgriSeedController::class, 'index'])->name('index');
        Route::get('{agri_seed}/edit', [AgriSeedController::class, 'edit'])->name('edit');
        Route::put('{agri_seed}', [AgriSeedController::class, 'update'])->name('update');
    });

    Route::prefix('farming-sources')->name('farming-sources.')->group(function () {
        Route::get('/', [FarmingSourceController::class, 'index'])->name('index');
        Route::post('/', [FarmingSourceController::class, 'store'])->name('store');
        Route::put('{farming_source}', [FarmingSourceController::class, 'update'])->name('update');
        Route::post('{farming_source}/confirm-pre-season', [FarmingSourceController::class, 'confirmPreSeason'])->name('confirm-pre-season');
    });

    Route::prefix('farming-batches')->name('farming-batches.')->group(function () {
        Route::get('/', [FarmingBatchController::class, 'index'])->name('index');
        Route::post('/', [FarmingBatchController::class, 'store'])->name('store');
        Route::get('{farming_batch}', [FarmingBatchController::class, 'show'])->name('show');
        Route::post('{farming_batch}/approve-harvest', [FarmingBatchController::class, 'approveHarvest'])->name('approve-harvest');
    });
});

Route::middleware(['auth', 'role_or_permission:farmer|compliance.manage'])->prefix('farmer')->name('farmer.')->group(function () {
    Route::get('dashboard', [FarmerDashboardController::class, 'index'])->name('dashboard');
    Route::get('batches/{farming_batch}/log', [FarmingLogController::class, 'create'])->name('batches.log.create');
    Route::post('batches/{farming_batch}/log', [FarmingLogController::class, 'store'])->name('batches.log.store');
    Route::get('logs/{farming_log}/edit', [FarmingLogController::class, 'edit'])->name('logs.edit');
    Route::put('logs/{farming_log}', [FarmingLogController::class, 'update'])->name('logs.update');
    Route::delete('logs/{farming_log}', [FarmingLogController::class, 'destroy'])->name('logs.destroy');
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('products', [ProductApiController::class, 'index'])->name('products');
    Route::get('document-master-types', [DocumentMasterTypeApiController::class, 'index'])->name('document-master-types');
    Route::get('partner-products', [PartnerProductApiController::class, 'index'])->name('partner-products');
    Route::get('categories', [CategoryApiController::class, 'index'])->name('categories');
    Route::get('agri-pesticides', [AgriPesticideApiController::class, 'index'])->name('agri-pesticides');
    Route::get('agri-fertilizers', [AgriFertilizerApiController::class, 'index'])->name('agri-fertilizers');
    Route::get('agri-seeds', [AgriSeedApiController::class, 'index'])->name('agri-seeds');
    Route::get('farming-sources', [FarmingSourceApiController::class, 'index'])->name('farming-sources');
    Route::get('farming-batches', [FarmingBatchApiController::class, 'index'])->name('farming-batches');
});
