<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\Api\FertilizerMobileApiController;
use Modules\Product\Http\Controllers\Api\PesticideMobileApiController;
use Modules\Product\Http\Controllers\Api\SeedMobileApiController;

Route::middleware(['auth:sanctum'])
    ->prefix('v1/master-data')
    ->name('master-data.')
    ->group(function () {
        Route::get('pesticides', [PesticideMobileApiController::class, 'index'])->name('pesticides');
        Route::get('fertilizers', [FertilizerMobileApiController::class, 'index'])->name('fertilizers');
        Route::get('seeds', [SeedMobileApiController::class, 'index'])->name('seeds');
    });
