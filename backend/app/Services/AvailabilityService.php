<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Space;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class AvailabilityService
{
    // Verifica si un espacio está disponible para las fechas dadas
    // Usa DB locking para evitar condiciones de carrera
    public function isAvailable(
        Space $space,
        string $checkIn,
        string $checkOut
    ): bool {
        // lockForUpdate bloquea las filas seleccionadas hasta que
        // la transacción termine — evita que dos usuarios reserven
        // el mismo espacio simultáneamente
        $conflict = Reservation::where('space_id', $space->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn)
            ->lockForUpdate()
            ->exists();

        return !$conflict;
    }

    // Obtiene las fechas bloqueadas del espacio (para el calendario del frontend)
    public function getBlockedDates(Space $space): array
    {
        // Traemos todas las reservas activas del espacio
        $reservations = $space->reservations()
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('check_out', '>=', Carbon::today())
            ->select('check_in', 'check_out')
            ->get();

        $blockedDates = [];

        foreach ($reservations as $reservation) {
            // CarbonPeriod genera todas las fechas entre check_in y check_out
            // Equivale a un rango de fechas
            $period = CarbonPeriod::create(
                $reservation->check_in,
                $reservation->check_out->subDay() // check_out no está bloqueado
            );

            foreach ($period as $date) {
                $blockedDates[] = $date->format('Y-m-d');
            }
        }

        // Eliminamos duplicados y ordenamos
        return array_values(array_unique($blockedDates));
    }

    // Calcula el precio total para una estadía
    public function calculatePrice(
        Space $space,
        string $checkIn,
        string $checkOut
    ): array {
        $nights = Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));

        if ($nights < 1) {
            abort(422, 'La estadía mínima es de 1 noche');
        }

        if ($nights > 30) {
            abort(422, 'La estadía máxima es de 30 noches');
        }

        $pricePerNight  = (float) $space->price_per_night;
        $subtotal       = $pricePerNight * $nights;

        // Descuento por estadía larga (opcional — lo dejamos para el frontend)
        $discount        = $this->calculateDiscount($nights, $subtotal);
        $total           = $subtotal - $discount;

        return [
            'nights'          => $nights,
            'price_per_night' => $pricePerNight,
            'subtotal'        => round($subtotal, 2),
            'discount'        => round($discount, 2),
            'discount_reason' => $discount > 0 ? $this->getDiscountReason($nights) : null,
            'total'           => round($total, 2),
        ];
    }

    // Descuentos por estadía larga — lógica de negocio
    private function calculateDiscount(int $nights, float $subtotal): float
    {
        return match (true) {
            $nights >= 28 => $subtotal * 0.20, // 20% por mes completo
            $nights >= 14 => $subtotal * 0.10, // 10% por 2 semanas
            $nights >= 7  => $subtotal * 0.05, // 5% por semana
            default       => 0,
        };
    }

    private function getDiscountReason(int $nights): string
    {
        return match (true) {
            $nights >= 28 => 'Descuento mensual (20%)',
            $nights >= 14 => 'Descuento quincenal (10%)',
            $nights >= 7  => 'Descuento semanal (5%)',
            default       => '',
        };
    }
}
