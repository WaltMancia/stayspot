<?php

namespace App\Events;

use App\Models\Reservation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// ShouldBroadcast → este evento se transmite por WebSocket
class ReservationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Reservation $reservation
    ) {}

    // El canal donde se transmite el evento
    // Channel público → cualquiera puede escuchar
    // PrivateChannel → requiere autenticación
    public function broadcastOn(): array
    {
        return [
            // Canal por espacio — solo quienes ven este espacio reciben el update
            new Channel("space.{$this->reservation->space_id}"),
        ];
    }

    // Nombre del evento en el frontend
    public function broadcastAs(): string
    {
        return 'availability.updated';
    }

    // Datos que se envían al frontend
    public function broadcastWith(): array
    {
        return [
            'space_id'   => $this->reservation->space_id,
            'check_in'   => $this->reservation->check_in->format('Y-m-d'),
            'check_out'  => $this->reservation->check_out->format('Y-m-d'),
            'message'    => 'Disponibilidad actualizada',
        ];
    }
}
