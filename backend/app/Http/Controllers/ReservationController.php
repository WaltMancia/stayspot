<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Models\Space;
use App\Services\AvailabilityService;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReservationController extends Controller
{
    public function __construct(
        private ReservationService  $reservationService,
        private AvailabilityService $availabilityService
    ) {}

    // GET /api/reservations
    public function index(Request $request): AnonymousResourceCollection
    {
        $role = $request->user()->isHost() ? 'host' : 'guest';

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

        return (new ReservationResource($reservation))
            ->response()
            ->setStatusCode(201);
    }

    // PATCH /api/reservations/{reservation}/confirm
    public function confirm(
        Reservation $reservation,
        Request $request
    ): ReservationResource {
        $updated = $this->reservationService->confirmReservation(
            $reservation,
            $request->user()
        );

        return new ReservationResource($updated);
    }

    // PATCH /api/reservations/{reservation}/cancel
    public function cancel(
        Request $request,
        Reservation $reservation
    ): ReservationResource {
        $updated = $this->reservationService->cancelReservation(
            $reservation,
            $request->user(),
            $request->input('reason')
        );

        return new ReservationResource($updated);
    }

    // GET /api/spaces/{space}/availability
    // Devuelve las fechas bloqueadas para el calendario
    public function availability(Space $space): JsonResponse
    {
        if (!$space->is_active) {
            abort(404, 'Espacio no encontrado');
        }

        $blockedDates = $this->availabilityService->getBlockedDates($space);

        return response()->json([
            'space_id'      => $space->id,
            'blocked_dates' => $blockedDates,
        ]);
    }

    // GET /api/spaces/{space}/price-estimate
    // Calcula el precio estimado para unas fechas
    public function priceEstimate(Space $space, Request $request): JsonResponse
    {
        $request->validate([
            'check_in'  => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
        ]);

        $pricing = $this->availabilityService->calculatePrice(
            $space,
            $request->check_in,
            $request->check_out
        );

        $isAvailable = $this->availabilityService->isAvailable(
            $space,
            $request->check_in,
            $request->check_out
        );

        return response()->json([
            ...$pricing,
            'is_available' => $isAvailable,
            'space_id'     => $space->id,
        ]);
    }
}
