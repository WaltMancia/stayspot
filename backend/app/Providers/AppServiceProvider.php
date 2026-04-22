<?php

namespace App\Providers;

use App\Models\Reservation;
use App\Models\Space;
use App\Policies\ReservationPolicy;
use App\Policies\SpacePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Policies
        Gate::policy(Space::class, SpacePolicy::class);
        Gate::policy(Reservation::class, ReservationPolicy::class);

        // Rate Limiting personalizado
        // El de login es más estricto para proteger contra fuerza bruta
        RateLimiter::for('login', function (Request $request) {
            // Limita por email + IP — 5 intentos por minuto
            // Si alguien intenta hacer brute force, queda bloqueado
            return Limit::perMinute(5)
                ->by($request->input('email') . '|' . $request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            // 60 peticiones por minuto por usuario autenticado
            // O por IP si no está autenticado
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip());
        });
    }
}
