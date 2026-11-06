<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Endpoint Kesehatan Sistem (Soal 3 Tugas P10)
Route::get('/health', [HealthController::class, 'check'])
    ->withoutMiddleware('web');

