<?php

use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\MikroTikController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\TokenController;
use App\Http\Controllers\Api\UserAuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CaptiveController;


// ── Public ────────────────────────────────────────────────
Route::post('/admin/login', [AdminAuthController::class, 'login']);
Route::post('/user/login',  [UserAuthController::class, 'login']);

// endpoint login user WiFi (captive portal)
Route::post('/captive/login', [TokenController::class, 'login']);
// endpoint khusus OS (WAJIB)
Route::get('/generate_204', [CaptiveController::class, 'androidCheck']);
Route::get('/hotspot-detect.html', [CaptiveController::class, 'appleCheck']);

// ── Admin (butuh token Sanctum) ───────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/admin/logout',          [AdminAuthController::class, 'logout']);
    Route::get('/admin/profile',          [AdminAuthController::class, 'profile']);
    Route::put('/admin/profile',          [AdminAuthController::class, 'updateProfile']);

    // Token management
    Route::get('/tokens',                 [TokenController::class, 'index']);
    Route::post('/tokens/generate',       [TokenController::class, 'generate']);
    Route::post('/tokens/custom',         [TokenController::class, 'addCustom']);
    Route::delete('/tokens/{token}',      [TokenController::class, 'destroy']);
    Route::get('/tokens/export/pdf',      [TokenController::class, 'exportPdf']);

    // Settings
    Route::get('/settings',               [SettingsController::class, 'index']);
    Route::put('/settings',               [SettingsController::class, 'update']);

    // MikroTik
    Route::get('/mikrotik/devices',       [MikroTikController::class, 'devices']);
    Route::put('/mikrotik/speed',         [MikroTikController::class, 'setSpeed']);
    Route::post('/mikrotik/disconnect', [MikroTikController::class, 'disconnect'])
    ->middleware('auth:sanctum');
});