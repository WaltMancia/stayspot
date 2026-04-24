<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Space;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    public function __construct(
        private AvailabilityService $availabilityService
    ) {}

    public function createReservation(array $data, User $guest): Reservation
    {
        $space = Space::findOrFail($data['space_id']);

        // Validaciones de negocio antes de la transacción
        $this->validateBusinessRules($space, $data, $guest);

        // Calculamos el precio en el servidor — nunca del cliente
        $pricing = $this->availabilityService->calculatePrice(
            $space,
            $data['check_in'],
            $data['check_out']
        );

        // DB::transaction garantiza atomicidad
        // Si algo falla → rollback automático
        return DB::transaction(function () use ($space, $data, $guest, $pricing) {

            // Verificamos disponibilidad DENTRO de la transacción con lock
            // Esto previene la condición de carrera de reservas simultáneas
            if (!$this->availabilityService->isAvailable(
                $space,
                $data['check_in'],
                $data['check_out']
            )) {
                abort(422, 'El espacio ya no está disponible en las fechas seleccionadas');
            }

            $reservation = Reservation::create([
                'space_id'        => $space->id,
                'guest_id'        => $guest->id,
                'check_in'        => $data['check_in'],
                'check_out'       => $data['check_out'],
                'guests_count'    => $data['guests_count'],
                'price_per_night' => $pricing['price_per_night'],
                'total_price'     => $pricing['total'],
                'nights'          => $pricing['nights'],
                'status'          => 'pending',
            ]);

            return $reservation->load(['space.host', 'guest']);
        });
    }

    public function confirmReservation(
        Reservation $reservation,
        User $host
    ): Reservation {
        // Solo el host del espacio puede confirmar
        if ($reservation->space->host_id !== $host->id) {
            abort(403, 'No tienes permiso para confirmar esta reserva');
        }

        if (!$reservation->isPending()) {
            abort(422, 'Solo se pueden confirmar reservas pendientes');
        }

        $reservation->update(['status' => 'confirmed']);

        return $reservation->fresh();
    }

    public function cancelReservation(
        Reservation $reservation,
        User $user,
        ?string $reason = null
    ): Reservation {
        $isGuest = $reservation->guest_id === $user->id;
        $isHost  = $reservation->space->host_id === $user->id;

        if (!$isGuest && !$isHost && !$user->isAdmin()) {
            abort(403, 'No tienes permiso para cancelar esta reserva');
        }

        if (!$reservation->canBeCancelled()) {
            abort(422, 'Esta reserva no puede cancelarse en su estado actual');
        }

        // Política de cancelación: el huésped no puede cancelar con menos de 48h
        if ($isGuest && !$user->isAdmin()) {
            $checkIn = Carbon::parse($reservation->check_in);
            if ($checkIn->diffInHours(Carbon::now()) < 48) {
                abort(422, 'No puedes cancelar con menos de 48 horas de anticipación');
            }
        }

        $reservation->update([
            'status'              => 'cancelled',
            'cancellation_reason' => $reason,
        ]);

        return $reservation->fresh(['space', 'guest']);
    }

    public function getUserReservations(
        User $user,
        string $role = 'guest'
    ): LengthAwarePaginator {
        if ($role === 'host') {
            return Reservation::query()
                ->whereHas('space', fn($q) => $q->where('host_id', $user->id))
                ->with([
                    'space:id,name,city,price_per_night',
                    'guest:id,name,email,phone',
                ])
                ->latest()
                ->paginate(10);
        }

        return $user->reservations()
            ->with([
                'space:id,name,city,price_per_night',
                'space.host:id,name',
                'review',
            ])
            ->latest()
            ->paginate(10);
    }

    private function validateBusinessRules(
        Space $space,
        array $data,
        User $guest
    ): void {
        // El espacio debe estar activo
        if (!$space->is_active) {
            abort(404, 'Este espacio no está disponible');
        }

        // Un host no puede reservar su propio espacio
        if ($space->host_id === $guest->id) {
            abort(422, 'No puedes reservar tu propio espacio');
        }

        // Verificamos capacidad
        if ((int)$data['guests_count'] > $space->max_guests) {
            abort(422, "El espacio tiene capacidad máxima de {$space->max_guests} huéspedes");
        }

        // Check-in no puede ser en el pasado
        if (Carbon::parse($data['check_in'])->isPast()) {
            abort(422, 'El check-in no puede ser en el pasado');
        }
    }
}
