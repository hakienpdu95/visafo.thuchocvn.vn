<?php

use Illuminate\Support\Facades\Route;
use Modules\Menu\Http\Controllers\Api\MenuApiController;
use Modules\Menu\Http\Controllers\MenuController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('menus', MenuController::class)->except(['show']);
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('menus', [MenuApiController::class, 'index'])->name('menus');
});
