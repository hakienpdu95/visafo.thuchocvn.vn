<?php

use Illuminate\Support\Facades\Route;
use Modules\Employee\Http\Controllers\Api\EmployeeApiController;
use Modules\Employee\Http\Controllers\DepartmentController;
use Modules\Employee\Http\Controllers\EmployeeController;
use Modules\Employee\Http\Controllers\EmployeeHealthRecordController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('departments', DepartmentController::class)->except(['show']);

    Route::resource('employees', EmployeeController::class)->except(['show']);

    Route::post('employees/{employee}/health-records', [EmployeeHealthRecordController::class, 'store'])
        ->name('employees.health-records.store');
    Route::delete('employees/{employee}/health-records/{healthRecord}', [EmployeeHealthRecordController::class, 'destroy'])
        ->name('employees.health-records.destroy');
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('employees', [EmployeeApiController::class, 'index'])->name('employees');
});
