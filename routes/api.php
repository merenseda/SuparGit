<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

// ─── Kimlik Doğrulama & Genel Uç Noktalar ──────────────────────────────────
Route::post('/login', [ApiController::class, 'login']);
Route::get('/app-version', [ApiController::class, 'appVersion']);

// ─── Korumalı API Rotaları (Sanctum Token ile) ──────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ApiController::class, 'profile']);
    Route::post('/profile', [ApiController::class, 'updateProfile']);
    Route::put('/profile', [ApiController::class, 'updateProfile']);
    Route::get('/repos', [ApiController::class, 'repositories']);
    Route::get('/branches', [ApiController::class, 'branches']);
    Route::get('/team-members', [ApiController::class, 'teamMembers']);
    Route::post('/push', [ApiController::class, 'push']);
    Route::get('/logs', [ApiController::class, 'logs']);
    Route::post('/fcm-token', [ApiController::class, 'updateFcmToken']);
});
