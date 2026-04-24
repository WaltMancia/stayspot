<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ── Públicas ──────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login'])
        ->middleware('throttle:login');
});

// Espacios — lectura pública
Route::get('/spaces',         [SpaceController::class, 'index']);
Route::get('/spaces/{space}', [SpaceController::class, 'show']);

// Disponibilidad y precio — públicos para que el visitante
// pueda ver fechas antes de registrarse
Route::get(
    '/spaces/{space}/availability',
    [ReservationController::class, 'availability']
);
Route::get(
    '/spaces/{space}/price-estimate',
    [ReservationController::class, 'priceEstimate']
);

// Perfil público de host
Route::get('/users/{user}/spaces', [UserController::class, 'spaces']);

// ── Protegidas ────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('/logout',     [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::get('/me',          [AuthController::class, 'me']);
        Route::post('/refresh',    [AuthController::class, 'refresh']);
    });

    Route::put('/users/profile', [UserController::class, 'updateProfile']);

    // Espacios — escritura
    Route::middleware('role:host,admin')->group(function () {
        Route::get('/spaces/my-spaces', [SpaceController::class, 'mySpaces']);
        Route::get('/spaces/stats',     [SpaceController::class, 'stats']);
        Route::post('/spaces',          [SpaceController::class, 'store']);
        Route::put('/spaces/{space}',   [SpaceController::class, 'update']);
        Route::delete('/spaces/{space}', [SpaceController::class, 'destroy']);
    });

    // Reservas
    Route::get(
        '/reservations',
        [ReservationController::class, 'index']
    );
    Route::post(
        '/reservations',
        [ReservationController::class, 'store']
    );
    Route::get(
        '/reservations/{reservation}',
        [ReservationController::class, 'show']
    );

    Route::patch(
        '/reservations/{reservation}/confirm',
        [ReservationController::class, 'confirm']
    )
        ->middleware('role:host,admin');

    Route::patch(
        '/reservations/{reservation}/cancel',
        [ReservationController::class, 'cancel']
    );

    // Reseñas
    Route::post(
        '/reservations/{reservation}/review',
        [ReviewController::class, 'store']
    );
});
