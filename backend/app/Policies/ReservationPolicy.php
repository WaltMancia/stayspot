<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    public function before(User $user): ?bool
    {
        if ($user->isAdmin()) return true;
        return null;
    }

    // El huésped o el host del espacio pueden ver la reserva
    public function view(User $user, Reservation $reservation): bool
    {
        return $reservation->guest_id === $user->id
            || $reservation->space->host_id === $user->id;
    }
}
