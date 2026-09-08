<?php

use Illuminate\Support\Facades\Route;
use Modules\Organization\Http\Controllers\Api\OrganizationApiController;
use Modules\Organization\Http\Controllers\Api\OrganizationLogoController;
use Modules\Organization\Http\Controllers\OrganizationController;

/*
|--------------------------------------------------------------------------
| Organization Module — Web Routes
|--------------------------------------------------------------------------
*/

// ── Backend CRUD (admin panel — System_Admin / super-admin only) ───────────
// Authorization được enforce qua OrganizationPolicy::authorizeResource() trong constructor.
Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {
    Route::resource('organizations', OrganizationController::class);

    // ── Version/publish (SRS01-FR-ORG-005/008 — GAP_ANALYSIS_v1.0.md §3.3 ORG-02/03) ──
    Route::get('organizations/{organization}/versions', [OrganizationController::class, 'versions'])->name('organizations.versions');
    Route::post('organizations/{organization}/publish', [OrganizationController::class, 'publish'])->name('organizations.publish');

});

// ── Backend JSON API for Tabulator (session-based auth, same guard as admin panel) ─────
Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('organizations', [OrganizationApiController::class, 'index'])
        ->name('organizations');

    // Logo upload / delete
    Route::post('organizations/{organization}/logo',   [OrganizationLogoController::class, 'store'])->name('organizations.logo.store');
    Route::delete('organizations/{organization}/logo', [OrganizationLogoController::class, 'destroy'])->name('organizations.logo.destroy');
});
