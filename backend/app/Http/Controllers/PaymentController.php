<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    // POST /api/reservations/{reservation}/payment-intent
    // Crea el PaymentIntent y devuelve el client_secret al frontend
    public function createPaymentIntent(
        Reservation $reservation,
        Request $request
    ): JsonResponse {
        $result = $this->paymentService->createPaymentIntent(
            $reservation,
            $request->user()
        );

        return response()->json($result);
    }

    // POST /api/webhooks/stripe
    // Stripe llama a este endpoint cuando ocurre un evento
    // NO debe estar protegido por auth:sanctum — Stripe no envía tokens
    // La seguridad viene de la verificación de firma
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload   = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (!$signature) {
            return response()->json(['message' => 'Missing signature'], 400);
        }

        $this->paymentService->handleWebhook($payload, $signature);

        // Stripe espera un 200 — si no lo recibe, reintenta el webhook
        return response()->json(['message' => 'Webhook processed']);
    }

    // GET /api/reservations/{reservation}/payment-status
    // El frontend puede consultar el estado del pago
    public function paymentStatus(
        Reservation $reservation,
        Request $request
    ): JsonResponse {
        if ($reservation->guest_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json([
            'reservation_id'        => $reservation->id,
            'status'                => $reservation->status,
            'stripe_payment_status' => $reservation->stripe_payment_status,
            'is_paid'               => $reservation->isPaid(),
            'paid_at'               => $reservation->paid_at?->toISOString(),
        ]);
    }
}
