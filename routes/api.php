<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

// ─── Kimlik Doğrulama ───────────────────────────────────────────────────────
Route::post('/login', [ApiController::class, 'login']);

// ─── Korumalı API Rotaları (Sanctum Token ile) ──────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ApiController::class, 'profile']);
    Route::get('/repos', [ApiController::class, 'repositories']);
    Route::get('/branches', [ApiController::class, 'branches']);
    Route::get('/team-members', [ApiController::class, 'teamMembers']);
    Route::post('/push', [ApiController::class, 'push']);
    Route::get('/logs', [ApiController::class, 'logs']);
    Route::post('/fcm-token', [ApiController::class, 'updateFcmToken']);
});
