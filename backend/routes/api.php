<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MaterialController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

// Public routes: Login & Healthcheck
Route::post('/login', [AuthController::class, 'login']);
Route::get('/health', [HealthController::class, 'check']);

// Protected routes (Sanctum token required)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::apiResource('materials', MaterialController::class);
    Route::apiResource('products', MaterialController::class);
});
