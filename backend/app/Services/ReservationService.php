<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Space;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ReservationService
{
    public function createReservation(array $data, User $guest): Reservation
    {
        $space = Space::findOrFail($data['space_id']);

        // Validación de negocio — va en el service, no en el controller
        $this->validateReservationRules($space, $data, $guest);

        // Calculamos los campos derivados en el servidor — nunca del cliente
        $checkIn  = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);
        $nights   = $checkIn->diffInDays($checkOut);

        return Reservation::create([
            'space_id'        => $space->id,
            'guest_id'        => $guest->id,
            'check_in'        => $data['check_in'],
            'check_out'       => $data['check_out'],
            'guests_count'    => $data['guests_count'],
            'price_per_night' => $space->price_per_night,
            // total_price calculado en el servidor — nunca confiar en el cliente
            'total_price'     => $space->price_per_night * $nights,
            'nights'          => $nights,
            'status'          => 'pending',
        ]);
    }

    public function cancelReservation(
        Reservation $reservation,
        User $user,
        ?string $reason = null
    ): Reservation {
        // Verificamos que el usuario puede cancelar esta reserva
        $canCancel = $reservation->guest_id === $user->id  // el huésped
            || $reservation->space->host_id === $user->id  // el host
            || $user->isAdmin();                            // o un admin

        if (!$canCancel) {
            abort(403, 'No tienes permiso para cancelar esta reserva');
        }

        if (!$reservation->canBeCancelled()) {
            abort(422, 'Esta reserva no puede cancelarse en su estado actual');
        }

        $reservation->update([
            'status'               => 'cancelled',
            'cancellation_reason'  => $reason,
        ]);

        return $reservation->fresh();
    }

    public function getUserReservations(
        User $user,
        string $role = 'guest'
    ): LengthAwarePaginator {
        if ($role === 'host') {
            // El host ve las reservas de sus espacios
            return Reservation::query()
                ->whereHas('space', fn($q) => $q->where('host_id', $user->id))
                ->with([
                    'space:id,name,city,price_per_night',
                    'guest:id,name,email,phone',
                ])
                ->latest()
                ->paginate(10);
        }

        // El huésped ve sus propias reservas
        return $user->reservations()
            ->with([
                'space:id,name,city,price_per_night',
                'space.host:id,name',
            ])
            ->latest()
            ->paginate(10);
    }

    private function validateReservationRules(
        Space $space,
        array $data,
        User $guest
    ): void {
        // Un host no puede reservar su propio espacio
        if ($space->host_id === $guest->id) {
            abort(422, 'No puedes reservar tu propio espacio');
        }

        // Verificamos disponibilidad en la BD
        if (!$space->isAvailableFor($data['check_in'], $data['check_out'])) {
            abort(422, 'El espacio no está disponible en las fechas seleccionadas');
        }

        // Verificamos capacidad
        if ($data['guests_count'] > $space->max_guests) {
            abort(422, "El espacio tiene capacidad máxima de {$space->max_guests} huéspedes");
        }

        // Validamos que las fechas son futuras
        if (Carbon::parse($data['check_in'])->isPast()) {
            abort(422, 'El check-in no puede ser en el pasado');
        }
    }
}
