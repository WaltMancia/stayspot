<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'check_in'        => $this->check_in?->format('Y-m-d'),
            'check_out'       => $this->check_out?->format('Y-m-d'),
            'guests_count'    => $this->guests_count,
            'nights'          => $this->nights,
            'price_per_night' => (float) $this->price_per_night,
            'total_price'     => (float) $this->total_price,
            'status'          => $this->status,
            'created_at'      => $this->created_at?->toISOString(),

            // Campos derivados — calculados en PHP, no en BD
            'can_be_cancelled' => $this->canBeCancelled(),
            'can_be_reviewed'  => $this->canBeReviewed(),

            'space' => new SpaceResource($this->whenLoaded('space')),
            'guest' => new UserResource($this->whenLoaded('guest')),
            'review' => new ReviewResource($this->whenLoaded('review')),
        ];
    }
}
