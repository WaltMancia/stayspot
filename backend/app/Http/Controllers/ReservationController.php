<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReservationController extends Controller
{
    public function __construct(
        private ReservationService $reservationService
    ) {}

    // GET /api/reservations
    public function index(Request $request): AnonymousResourceCollection
    {
        $role = $request->user()->role === 'host' ? 'host' : 'guest';

        $reservations = $this->reservationService->getUserReservations(
            $request->user(),
            $role
        );

        return ReservationResource::collection($reservations);
    }

    // GET /api/reservations/{reservation}
    public function show(Reservation $reservation): ReservationResource
    {
        $this->authorize('view', $reservation);

        // Cargamos las relaciones necesarias para el detalle
        $reservation->load(['space.host', 'guest', 'review']);

        return new ReservationResource($reservation);
    }

    // POST /api/reservations
    public function store(StoreReservationRequest $request): JsonResponse
    {
        $reservation = $this->reservationService->createReservation(
            $request->validated(),
            $request->user()
        );

        $reservation->load(['space', 'guest']);

        return (new ReservationResource($reservation))
            ->response()
            ->setStatusCode(201);
    }

    // PATCH /api/reservations/{reservation}/cancel
    public function cancel(Request $request, Reservation $reservation): ReservationResource
    {
        $updated = $this->reservationService->cancelReservation(
            $reservation,
            $request->user(),
            $request->input('reason')
        );

        return new ReservationResource($updated);
    }
}
