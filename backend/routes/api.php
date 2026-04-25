<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ── Webhook — FUERA de cualquier middleware ───────────────────
// Stripe necesita el body sin parsear para verificar la firma
Route::post('/webhooks/stripe', [PaymentController::class, 'handleWebhook'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// ── Públicas ──────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login'])
        ->middleware('throttle:login');
});

Route::get('/spaces',         [SpaceController::class, 'index']);
Route::get('/spaces/{space}', [SpaceController::class, 'show']);

Route::get(
    '/spaces/{space}/availability',
    [ReservationController::class, 'availability']
);
Route::get(
    '/spaces/{space}/price-estimate',
    [ReservationController::class, 'priceEstimate']
);

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

    Route::middleware('role:host,admin')->group(function () {
        Route::get('/spaces/my-spaces', [SpaceController::class, 'mySpaces']);
        Route::get('/spaces/stats',     [SpaceController::class, 'stats']);
        Route::post('/spaces',          [SpaceController::class, 'store']);
        Route::put('/spaces/{space}',   [SpaceController::class, 'update']);
        Route::delete('/spaces/{space}', [SpaceController::class, 'destroy']);
    });

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

    // Pagos
    Route::post(
        '/reservations/{reservation}/payment-intent',
        [PaymentController::class, 'createPaymentIntent']
    );
    Route::get(
        '/reservations/{reservation}/payment-status',
        [PaymentController::class, 'paymentStatus']
    );

    Route::post(
        '/reservations/{reservation}/review',
        [ReviewController::class, 'store']
    );
});
