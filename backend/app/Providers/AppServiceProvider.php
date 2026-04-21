<?php

namespace App\Providers;

use App\Models\Reservation;
use App\Models\Space;
use App\Policies\ReservationPolicy;
use App\Policies\SpacePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Gate::policy vincula un modelo con su policy
        Gate::policy(Space::class, SpacePolicy::class);
        Gate::policy(Reservation::class, ReservationPolicy::class);
    }
}
