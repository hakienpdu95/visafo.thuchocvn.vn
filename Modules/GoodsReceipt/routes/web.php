<?php

use Illuminate\Support\Facades\Route;
use Modules\GoodsReceipt\Http\Controllers\Api\GoodsReceiptApiController;
use Modules\GoodsReceipt\Http\Controllers\GoodsReceiptController;
use Modules\GoodsReceipt\Http\Controllers\GoodsReceiptImportController;
use Modules\GoodsReceipt\Http\Controllers\ProductBatchController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::get('goods-receipts/import', [GoodsReceiptImportController::class, 'create'])->name('goods-receipts.import');
    Route::post('goods-receipts/import', [GoodsReceiptImportController::class, 'store'])->name('goods-receipts.import.store');

    Route::get('goods-receipts', [GoodsReceiptController::class, 'index'])->name('goods-receipts.index');
    Route::get('goods-receipts/{goods_receipt}', [GoodsReceiptController::class, 'show'])->name('goods-receipts.show');

    Route::put('product-batches/{product_batch}', [ProductBatchController::class, 'update'])->name('product-batches.update');
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('goods-receipts', [GoodsReceiptApiController::class, 'index'])->name('goods-receipts');
});
