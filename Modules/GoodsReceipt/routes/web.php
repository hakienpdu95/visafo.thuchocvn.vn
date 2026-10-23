<?php

use Illuminate\Support\Facades\Route;
use Modules\GoodsReceipt\Http\Controllers\Api\GoodsReceiptApiController;
use Modules\GoodsReceipt\Http\Controllers\Api\GoodsReceiptOptionApiController;
use Modules\GoodsReceipt\Http\Controllers\GoodsReceiptController;
use Modules\GoodsReceipt\Http\Controllers\GoodsReceiptDocumentController;
use Modules\GoodsReceipt\Http\Controllers\GoodsReceiptImportController;
use Modules\GoodsReceipt\Http\Controllers\ProductBatchController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::get('goods-receipts/import', [GoodsReceiptImportController::class, 'create'])->name('goods-receipts.import');
    Route::post('goods-receipts/import', [GoodsReceiptImportController::class, 'store'])->name('goods-receipts.import.store');

    Route::get('goods-receipts/create', [GoodsReceiptController::class, 'create'])->name('goods-receipts.create');
    Route::post('goods-receipts', [GoodsReceiptController::class, 'store'])->name('goods-receipts.store');

    Route::get('goods-receipts', [GoodsReceiptController::class, 'index'])->name('goods-receipts.index');
    Route::get('goods-receipts/{goods_receipt}', [GoodsReceiptController::class, 'show'])->name('goods-receipts.show');
    Route::get('goods-receipts/{goods_receipt}/edit', [GoodsReceiptController::class, 'edit'])->name('goods-receipts.edit');
    Route::put('goods-receipts/{goods_receipt}', [GoodsReceiptController::class, 'update'])->name('goods-receipts.update');
    Route::delete('goods-receipts/{goods_receipt}', [GoodsReceiptController::class, 'destroy'])->name('goods-receipts.destroy');
    Route::post('goods-receipts/{goods_receipt}/documents', [GoodsReceiptDocumentController::class, 'store'])->name('goods-receipts.documents.store');
    Route::delete('goods-receipts/{goods_receipt}/documents/{document}', [GoodsReceiptDocumentController::class, 'destroy'])->name('goods-receipts.documents.destroy');

    Route::put('product-batches/{product_batch}', [ProductBatchController::class, 'update'])->name('product-batches.update');
    Route::post('product-batches/{product_batch}/quality-checks', [ProductBatchController::class, 'storeQualityCheck'])->name('product-batches.quality-checks.store');
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('goods-receipts', [GoodsReceiptApiController::class, 'index'])->name('goods-receipts');
    Route::get('goods-receipts/options/products', [GoodsReceiptOptionApiController::class, 'products'])->name('goods-receipts.product-options');
    Route::get('goods-receipts/options/vendors', [GoodsReceiptOptionApiController::class, 'vendors'])->name('goods-receipts.vendor-options');
});
