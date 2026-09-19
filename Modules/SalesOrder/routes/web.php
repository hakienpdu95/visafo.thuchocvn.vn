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
    Route::get('sales-orders/items/{item}/print-logs', [PrintLabelController::class, 'history'])->name('sales-orders.items.print-logs');
    Route::post('sales-orders/{sales_order}/print-all', [PrintLabelController::class, 'storeAll'])->name('sales-orders.print-all');
    Route::get('sales-orders/{sales_order}/labels', [PrintLabelController::class, 'labels'])->name('sales-orders.labels');
    Route::get('sales-orders/print-logs/{print_log}/label', [PrintLabelController::class, 'label'])->name('sales-orders.print-logs.label');

    Route::get('sales-orders', [SalesOrderController::class, 'index'])->name('sales-orders.index');
    Route::get('sales-orders/{sales_order}', [SalesOrderController::class, 'show'])->name('sales-orders.show');
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('sales-orders', [SalesOrderApiController::class, 'index'])->name('sales-orders');
});
