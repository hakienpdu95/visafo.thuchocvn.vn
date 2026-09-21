<?php

use Illuminate\Support\Facades\Route;
use Modules\FoodInspection\Http\Controllers\Api\FoodInspectionStep1ApiController;
use Modules\FoodInspection\Http\Controllers\Api\FoodSampleApiController;
use Modules\FoodInspection\Http\Controllers\Api\FoodInspectionStep2ApiController;
use Modules\FoodInspection\Http\Controllers\Api\FoodInspectionStep3ApiController;
use Modules\FoodInspection\Http\Controllers\FoodInspectionStep1Controller;
use Modules\FoodInspection\Http\Controllers\FoodSampleController;
use Modules\FoodInspection\Http\Controllers\FoodInspectionStep2Controller;
use Modules\FoodInspection\Http\Controllers\FoodInspectionStep3Controller;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::get('food-inspections', [FoodInspectionStep1Controller::class, 'index'])->name('food-inspections.index');
    Route::get('food-inspections/create', [FoodInspectionStep1Controller::class, 'create'])->name('food-inspections.create');
    Route::post('food-inspections', [FoodInspectionStep1Controller::class, 'store'])->name('food-inspections.store');
    Route::get('food-inspections/{food_inspection}', [FoodInspectionStep1Controller::class, 'show'])->name('food-inspections.show');
    Route::get('food-inspections/{food_inspection}/edit', [FoodInspectionStep1Controller::class, 'edit'])->name('food-inspections.edit');
    Route::match(['put', 'patch'], 'food-inspections/{food_inspection}', [FoodInspectionStep1Controller::class, 'update'])->name('food-inspections.update');
    Route::delete('food-inspections/{food_inspection}', [FoodInspectionStep1Controller::class, 'destroy'])->name('food-inspections.destroy');
});

// Sổ kiểm thực Bước 2 (kiểm tra khi chế biến)
Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::get('food-inspection-step2/menu-dishes', [FoodInspectionStep2Controller::class, 'menuDishes'])->name('food-inspection-step2.menu-dishes');
    Route::resource('food-inspection-step2', FoodInspectionStep2Controller::class)->parameters(['food-inspection-step2' => 'food_inspection_step2']);
});

// Sổ kiểm thực Bước 3 (kiểm tra trước khi ăn)
Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::get('food-inspection-step3/source-dishes', [FoodInspectionStep3Controller::class, 'sourceDishes'])->name('food-inspection-step3.source-dishes');
    Route::resource('food-inspection-step3', FoodInspectionStep3Controller::class)->parameters(['food-inspection-step3' => 'food_inspection_step3']);
});

// Lưu & hủy mẫu thức ăn (Mẫu số 4 & 5)
Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::get('food-samples/source-dishes', [FoodSampleController::class, 'sourceDishes'])->name('food-samples.source-dishes');
    Route::get('food-samples/{food_sample}/labels', [FoodSampleController::class, 'labels'])->name('food-samples.labels');
    Route::resource('food-samples', FoodSampleController::class)->parameters(['food-samples' => 'food_sample']);
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('food-inspections', [FoodInspectionStep1ApiController::class, 'index'])->name('food-inspections');
    Route::get('food-inspection-step2', [FoodInspectionStep2ApiController::class, 'index'])->name('food-inspection-step2');
    Route::get('food-inspection-step3', [FoodInspectionStep3ApiController::class, 'index'])->name('food-inspection-step3');
    Route::get('food-samples', [FoodSampleApiController::class, 'index'])->name('food-samples');
});
