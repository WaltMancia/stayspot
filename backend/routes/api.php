<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ── Rutas públicas ────────────────────────────────────────────
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login',    [AuthController::class, 'login']);

// Espacios — lectura pública
Route::get('/spaces',         [SpaceController::class, 'index']);
Route::get('/spaces/{space}', [SpaceController::class, 'show']);

// ── Rutas protegidas ──────────────────────────────────────────
// middleware('auth:sanctum') → requiere token válido de Sanctum
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);

    // Perfil de usuario
    Route::put('/users/profile', [UserController::class, 'updateProfile']);

    // Espacios — escritura protegida
    // middleware('role:host') → solo hosts
    Route::middleware('role:host,admin')->group(function () {
        Route::post('/spaces',           [SpaceController::class, 'store']);
        Route::put('/spaces/{space}',    [SpaceController::class, 'update']);
        Route::delete('/spaces/{space}', [SpaceController::class, 'destroy']);
    });

    // Reservas
    Route::get('/reservations',               [ReservationController::class, 'index']);
    Route::post('/reservations',              [ReservationController::class, 'store']);
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
    Route::patch('/reservations/{reservation}/cancel',
        [ReservationController::class, 'cancel']);

    // Reseñas
    Route::post('/reservations/{reservation}/review',
        [ReviewController::class, 'store']);
});