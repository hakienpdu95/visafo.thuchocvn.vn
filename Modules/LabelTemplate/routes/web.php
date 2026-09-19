<?php

use Illuminate\Support\Facades\Route;
use Modules\LabelTemplate\Http\Controllers\Api\LabelTemplateApiController;
use Modules\LabelTemplate\Http\Controllers\LabelTemplateController;

Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::get('label-templates/{label_template}/preview', [LabelTemplateController::class, 'preview'])->name('label-templates.preview');
    Route::resource('label-templates', LabelTemplateController::class)->except(['show']);
});

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('label-templates', [LabelTemplateApiController::class, 'index'])->name('label-templates');
});
