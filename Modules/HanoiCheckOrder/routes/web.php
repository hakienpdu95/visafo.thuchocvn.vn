<?php

use Illuminate\Support\Facades\Route;
use Modules\HanoiCheckOrder\Http\Controllers\Api\HanoiCheckOrderApiController;
use Modules\HanoiCheckOrder\Http\Controllers\HanoiCheckOrderController;
use Modules\HanoiCheckOrder\Http\Controllers\HanoiCheckTransporterController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::get('hanoicheck-orders', [HanoiCheckOrderController::class, 'index'])->name('hanoicheck-orders.index');
    Route::get('hanoicheck-orders/remote', [HanoiCheckOrderController::class, 'remote'])->name('hanoicheck-orders.remote');
    Route::put('hanoicheck-orders/{sales_order}/link', [HanoiCheckOrderController::class, 'link'])->name('hanoicheck-orders.link');
    Route::get('hanoicheck-orders/{sales_order}/detail', [HanoiCheckOrderController::class, 'detail'])->name('hanoicheck-orders.detail');
    Route::post('hanoicheck-orders/{sales_order}/delivery', [HanoiCheckOrderController::class, 'delivery'])->name('hanoicheck-orders.delivery');
    Route::post('hanoicheck-orders/{sales_order}/sync', [HanoiCheckOrderController::class, 'sync'])->name('hanoicheck-orders.sync');

    Route::get('hanoicheck-transporters', [HanoiCheckTransporterController::class, 'index'])->name('hanoicheck-transporters.index');
    Route::get('hanoicheck-transporters/list', [HanoiCheckTransporterController::class, 'list'])->name('hanoicheck-transporters.list');
    Route::post('hanoicheck-transporters', [HanoiCheckTransporterController::class, 'store'])->name('hanoicheck-transporters.store');
    Route::put('hanoicheck-transporters/{transporter}', [HanoiCheckTransporterController::class, 'update'])->name('hanoicheck-transporters.update');
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('hanoicheck-orders', [HanoiCheckOrderApiController::class, 'index'])->name('hanoicheck-orders');
});
