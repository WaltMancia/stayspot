<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\User;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class PaymentService
{
    private StripeClient $stripe;

    public function __construct()
    {
        // Instanciamos el cliente de Stripe con la secret key
        $this->stripe = new StripeClient(
            config('services.stripe.secret')
        );
    }

    // Crea o recupera un PaymentIntent para una reserva
    // IDEMPOTENCIA: si ya existe un PaymentIntent para esta reserva,
    // devolvemos el mismo en vez de crear uno nuevo
    public function createPaymentIntent(
        Reservation $reservation,
        User $guest
    ): array {
        // Verificamos que la reserva pertenece al usuario autenticado
        if ($reservation->guest_id !== $guest->id) {
            abort(403, 'No tienes acceso a esta reserva');
        }

        // Solo se pueden pagar reservas pendientes
        if ($reservation->status !== 'pending') {
            abort(422, 'Esta reserva no puede procesarse para pago');
        }

        // IDEMPOTENCIA: si ya tiene un PaymentIntent, lo recuperamos
        // Esto evita cobrar dos veces si el usuario recarga la página
        if ($reservation->stripe_payment_intent_id) {
            try {
                $intent = $this->stripe->paymentIntents->retrieve(
                    $reservation->stripe_payment_intent_id
                );

                // Si el intent anterior fue cancelado, creamos uno nuevo
                if ($intent->status !== 'canceled') {
                    return [
                        'client_secret'    => $intent->client_secret,
                        'payment_intent_id' => $intent->id,
                        'amount'           => $intent->amount / 100,
                    ];
                }
            } catch (ApiErrorException $e) {
                // Si no se puede recuperar, creamos uno nuevo
            }
        }

        try {
            // Creamos el PaymentIntent en Stripe
            $intent = $this->stripe->paymentIntents->create([
                // amount en centavos — Stripe siempre trabaja en la unidad más pequeña
                // $150.00 → 15000 centavos
                'amount'   => (int)($reservation->total_price * 100),
                'currency' => 'usd',

                // metadata vincula el pago con nuestra reserva
                // Lo usaremos en el webhook para identificar la reserva
                'metadata' => [
                    'reservation_id' => $reservation->id,
                    'space_id'       => $reservation->space_id,
                    'guest_id'       => $guest->id,
                    'check_in'       => $reservation->check_in->format('Y-m-d'),
                    'check_out'      => $reservation->check_out->format('Y-m-d'),
                ],

                // Descripción que aparece en el dashboard de Stripe
                'description' => "Reserva #{$reservation->id} - " .
                    $reservation->space->name,

                // automatic_payment_methods → Stripe decide el método más apropiado
                // según la ubicación del cliente (tarjeta, OXXO, etc.)
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            // Guardamos el ID del PaymentIntent para idempotencia futura
            $reservation->update([
                'stripe_payment_intent_id' => $intent->id,
                'stripe_payment_status'    => $intent->status,
            ]);

            return [
                'client_secret'     => $intent->client_secret,
                'payment_intent_id' => $intent->id,
                'amount'            => $reservation->total_price,
            ];
        } catch (ApiErrorException $e) {
            // Loguemos el error de Stripe sin exponer detalles al cliente
            \Log::error('Stripe PaymentIntent creation failed', [
                'reservation_id' => $reservation->id,
                'error'          => $e->getMessage(),
            ]);

            abort(502, 'Error al procesar el pago. Inténtalo de nuevo.');
        }
    }

    // Procesa el webhook de Stripe
    // Actualiza el estado de la reserva según el evento recibido
    public function handleWebhook(string $payload, string $signature): void
    {
        try {
            // constructEvent verifica criptográficamente que el evento
            // viene de Stripe y no de un atacante
            // Si la firma no coincide → lanza SignatureVerificationException
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Si alguien intenta falsificar un webhook → 400
            abort(400, 'Firma de webhook inválida');
        }

        // Procesamos solo los eventos que nos interesan
        // Ignoramos el resto sin error — Stripe envía muchos tipos de eventos
        match ($event->type) {
            'payment_intent.succeeded'
            => $this->handlePaymentSucceeded($event->data->object),
            'payment_intent.payment_failed'
            => $this->handlePaymentFailed($event->data->object),
            default => null,
        };
    }

    private function handlePaymentSucceeded(
        \Stripe\PaymentIntent $paymentIntent
    ): void {
        $reservation = Reservation::where(
            'stripe_payment_intent_id',
            $paymentIntent->id
        )->first();

        if (!$reservation) {
            \Log::warning('Reservation not found for PaymentIntent', [
                'payment_intent_id' => $paymentIntent->id,
            ]);
            return;
        }

        // Evitamos procesar el mismo evento dos veces (idempotencia del webhook)
        if ($reservation->isPaid()) {
            return;
        }

        $reservation->update([
            'status'                => 'confirmed',
            'stripe_payment_status' => 'succeeded',
            'paid_at'               => now(),
        ]);

        \Log::info("Reservation #{$reservation->id} confirmed after payment");

        // Aquí dispararíamos el evento para notificar al host
        // event(new ReservationPaid($reservation));
    }

    private function handlePaymentFailed(
        \Stripe\PaymentIntent $paymentIntent
    ): void {
        $reservation = Reservation::where(
            'stripe_payment_intent_id',
            $paymentIntent->id
        )->first();

        if (!$reservation) return;

        $reservation->update([
            'stripe_payment_status' => 'failed',
        ]);

        \Log::warning("Payment failed for reservation #{$reservation->id}");
    }
}
