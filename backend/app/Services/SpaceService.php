<?php

namespace App\Services;

use App\Models\Space;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SpaceService
{
    // Búsqueda con filtros, eager loading y paginación
    public function search(array $filters): LengthAwarePaginator
    {
        return Space::query()
            // Eager loading — cargamos host y el promedio de reviews en 2 queries
            ->with('host:id,name,avatar')   // solo los campos que necesitamos
            ->withCount('reviews')          // añade reviews_count al resultado
            ->withAvg('reviews', 'rating')  // añade reviews_avg_rating

            // Aplicamos scopes del modelo
            ->active()
            ->when(
                // when(condicion, closure) → aplica el closure solo si condicion es true
                // Equivale al filtro dinámico con if en el repositorio de Python
                !empty($filters['city']),
                fn($q) => $q->inCity($filters['city'])
            )
            ->when(
                !empty($filters['price_min']) || !empty($filters['price_max']),
                fn($q) => $q->priceBetween(
                    $filters['price_min'] ?? null,
                    $filters['price_max'] ?? null
                )
            )
            ->when(
                !empty($filters['guests']),
                fn($q) => $q->where('max_guests', '>=', (int)$filters['guests'])
            )
            ->when(
                !empty($filters['check_in']) && !empty($filters['check_out']),
                fn($q) => $q->whereDoesntHave('reservations', function ($q) use ($filters) {
                    // whereDoesntHave → WHERE NOT EXISTS (subquery)
                    // Filtra espacios que NO tienen reservas solapadas
                    $q->whereIn('status', ['pending', 'confirmed'])
                        ->where('check_in', '<', $filters['check_out'])
                        ->where('check_out', '>', $filters['check_in']);
                })
            )
            ->when(
                !empty($filters['sort']),
                function ($q) use ($filters) {
                    return match ($filters['sort']) {
                        'price_asc'  => $q->orderBy('price_per_night'),
                        'price_desc' => $q->orderByDesc('price_per_night'),
                        'rating'     => $q->orderByDesc('reviews_avg_rating'),
                        'newest'     => $q->orderByDesc('created_at'),
                        default      => $q->orderByDesc('created_at'),
                    };
                },
                // Si no hay sort → ordenamos por created_at por defecto
                fn($q) => $q->orderByDesc('created_at')
            )
            ->paginate(
                perPage: min(24, max(1, (int)($filters['per_page'] ?? 12))),
                page: max(1, (int)($filters['page'] ?? 1))
            );
    }

    public function findWithDetails(int $id): Space
    {
        // findOrFail → lanza ModelNotFoundException (404) si no existe
        // Laravel lo convierte automáticamente en respuesta 404
        return Space::with([
            'host:id,name,avatar,created_at',
            // En reviews cargamos el guest solo con los campos necesarios
            'reviews' => fn($q) => $q->with('guest:id,name,avatar')
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
        // El host_id siempre viene del usuario autenticado
        // NUNCA del request — el cliente no decide de quién es el espacio
        return Space::create([
            ...$data,  // spread operator en PHP 8+
            'host_id' => $host->id,
        ]);
    }

    public function updateSpace(Space $space, array $data): Space
    {
        $space->update($data);
        // Recargamos el modelo para tener los datos actualizados
        return $space->fresh();
    }

    public function deleteSpace(Space $space): void
    {
        // Soft delete lógico — marcamos como inactivo en vez de borrar
        $space->update(['is_active' => false]);
    }

    // Estadísticas del host — dashboard del anfitrión
    public function getHostStats(User $host): array
    {
        // withCount añade *_count al modelo sin query adicional
        $spaces = $host->spaces()
            ->withCount(['reservations', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->get();

        // Ingresos totales de reservas confirmadas y completadas
        $totalRevenue = $host->spaces()
            ->join('reservations', 'spaces.id', '=', 'reservations.space_id')
            ->whereIn('reservations.status', ['confirmed', 'completed'])
            ->sum('reservations.total_price');

        // Reservas pendientes de confirmación
        $pendingReservations = $host->spaces()
            ->join('reservations', 'spaces.id', '=', 'reservations.space_id')
            ->where('reservations.status', 'pending')
            ->count();

        return [
            'total_spaces'         => $spaces->count(),
            'active_spaces'        => $spaces->where('is_active', true)->count(),
            'total_revenue'        => round((float)$totalRevenue, 2),
            'pending_reservations' => $pendingReservations,
            'average_rating'       => round(
                $spaces->whereNotNull('reviews_avg_rating')
                    ->avg('reviews_avg_rating') ?? 0,
                1
            ),
        ];
    }
}
