<?php

use Illuminate\Support\Facades\Route;
use Modules\SalesOrder\Http\Controllers\Api\SalesOrderApiController;
use Modules\SalesOrder\Http\Controllers\PrintLabelController;
use Modules\SalesOrder\Http\Controllers\SalesOrderController;
use Modules\SalesOrder\Http\Controllers\SalesOrderImportController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::get('sales-orders/import', [SalesOrderImportController::class, 'create'])->name('sales-orders.import');
    Route::post('sales-orders/import', [SalesOrderImportController::class, 'store'])->name('sales-orders.import.store');

    Route::post('sales-orders/items/{item}/print', [PrintLabelController::class, 'store'])->name('sales-orders.items.print');
    Route::get('sales-orders/items/{item}/batch-attributes', [PrintLabelController::class, 'batchAttributes'])->name('sales-orders.items.batch-attributes');
    Route::get('sales-orders/items/{item}/print-logs', [PrintLabelController::class, 'history'])->name('sales-orders.items.print-logs');
    Route::post('sales-orders/{sales_order}/print-all', [PrintLabelController::class, 'storeAll'])->name('sales-orders.print-all');
    Route::get('sales-orders/{sales_order}/labels', [PrintLabelController::class, 'labels'])->name('sales-orders.labels');
    Route::get('sales-orders/print-logs/{print_log}/reprint', [PrintLabelController::class, 'reprint'])->name('sales-orders.print-logs.reprint');
    Route::get('sales-orders/print-logs/{print_log}/label', [PrintLabelController::class, 'label'])->name('sales-orders.print-logs.label');

    Route::get('sales-orders', [SalesOrderController::class, 'index'])->name('sales-orders.index');
    Route::get('sales-orders/{sales_order}', [SalesOrderController::class, 'show'])->name('sales-orders.show');
});

// Render tem động (route name: print.render) — dùng view_path của mẫu tem gán cho sản phẩm.
Route::middleware(['auth'])->prefix('dashboard')->group(function () {
    Route::get('print-logs/{print_log}/render', [PrintLabelController::class, 'render'])->name('print.render');
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('sales-orders', [SalesOrderApiController::class, 'index'])->name('sales-orders');
});
