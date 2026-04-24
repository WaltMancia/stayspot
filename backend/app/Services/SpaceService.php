<?php

namespace App\Services;

use App\Models\Space;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SpaceService
{
    public function search(array $filters): LengthAwarePaginator
    {
        return Space::query()
            ->with('host:id,name,avatar')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->active()
            ->when(
                !empty($filters['city']),
                fn($q) => $q->inCity($filters['city'])
            )
            ->when(
                !empty($filters['price_min']) || !empty($filters['price_max']),
                fn($q) => $q->priceBetween(
                    isset($filters['price_min'])
                        ? (float)$filters['price_min'] : null,
                    isset($filters['price_max'])
                        ? (float)$filters['price_max'] : null
                )
            )
            ->when(
                !empty($filters['guests']),
                fn($q) => $q->where('max_guests', '>=', (int)$filters['guests'])
            )
            ->when(
                !empty($filters['check_in']) && !empty($filters['check_out']),
                fn($q) => $q->whereDoesntHave('reservations', function ($q) use ($filters) {
                    $q->whereIn('status', ['pending', 'confirmed'])
                      ->where('check_in', '<', $filters['check_out'])
                      ->where('check_out', '>', $filters['check_in']);
                })
            )
            ->when(
                !empty($filters['sort']),
                fn($q) => match($filters['sort']) {
                    'price_asc'  => $q->orderBy('price_per_night'),
                    'price_desc' => $q->orderByDesc('price_per_night'),
                    'rating'     => $q->orderByDesc('reviews_avg_rating'),
                    default      => $q->orderByDesc('created_at'),
                },
                fn($q) => $q->orderByDesc('created_at')
            )
            ->paginate(
                perPage: min(24, max(1, (int)($filters['per_page'] ?? 12))),
                page: max(1, (int)($filters['page'] ?? 1))
            );
    }

    public function findWithDetails(int $id): Space
    {
        return Space::with([
            'host:id,name,avatar,created_at',
            'reviews' => fn($q) => $q
                ->with('guest:id,name,avatar')
                ->latest()
                ->limit(10),
        ])
        ->withCount('reviews')
        ->withAvg('reviews', 'rating')
        ->where('is_active', true)
        ->findOrFail($id);
    }

    public function createSpace(array $data, User $host): Space
    {
        // SEGURIDAD: el host_id siempre viene del servidor — nunca del cliente
        // Aunque alguien envíe host_id en el request, aquí lo sobreescribimos
        $space = Space::create([
            ...$this->sanitizeSpaceData($data),
            'host_id'  => $host->id,
            'is_active' => true,
        ]);

        return $space->load('host');
    }

    public function updateSpace(Space $space, array $data): Space
    {
        // SEGURIDAD: no permitimos cambiar el host ni el estado activo
        // desde la actualización normal
        unset($data['host_id'], $data['is_active']);

        $space->update($this->sanitizeSpaceData($data));

        return $space->fresh(['host', 'reviews']);
    }

    public function deleteSpace(Space $space): void
    {
        // Soft delete — verificamos si tiene reservas activas
        $hasActiveReservations = $space->reservations()
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();

        if ($hasActiveReservations) {
            abort(422, 'No puedes eliminar un espacio con reservas activas. ' .
                       'Cancela las reservas primero.');
        }

        $space->update(['is_active' => false]);
    }

    public function getHostStats(User $host): array
    {
        $spaces = $host->spaces()
            ->withCount(['reservations', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->get();

        $totalRevenue = $host->spaces()
            ->join('reservations', 'spaces.id', '=', 'reservations.space_id')
            ->whereIn('reservations.status', ['confirmed', 'completed'])
            ->sum('reservations.total_price');

        $pendingReservations = $host->spaces()
            ->join('reservations', 'spaces.id', '=', 'reservations.space_id')
            ->where('reservations.status', 'pending')
            ->count();

        return [
            'total_spaces'         => $spaces->count(),
            'active_spaces'        => $spaces->where('is_active', true)->count(),
            'total_revenue'        => round((float) $totalRevenue, 2),
            'pending_reservations' => $pendingReservations,
            'average_rating'       => round(
                (float) ($spaces->whereNotNull('reviews_avg_rating')
                                ->avg('reviews_avg_rating') ?? 0),
                1
            ),
        ];
    }

    // Sanitización de datos del espacio
    private function sanitizeSpaceData(array $data): array
    {
        $sanitized = [];

        // Solo procesamos los campos permitidos
        $allowed = [
            'name', 'description', 'city', 'address', 'country',
            'price_per_night', 'max_guests', 'bedrooms', 'bathrooms',
            'amenities', 'latitude', 'longitude',
        ];

        foreach ($allowed as $field) {
            if (!array_key_exists($field, $data)) continue;

            $sanitized[$field] = match(true) {
                // strip_tags elimina HTML/JS del texto
                is_string($data[$field])
                    => strip_tags(trim($data[$field])),

                // Los arrays se sanitizan elemento por elemento
                is_array($data[$field])
                    => array_map(
                        fn($item) => strip_tags(trim((string) $item)),
                        $data[$field]
                    ),

                // Números y booleanos pasan directamente
                default => $data[$field],
            };
        }

        return $sanitized;
    }
}