<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'description'     => $this->description,
            'city'            => $this->city,
            'address'         => $this->address,
            'country'         => $this->country,
            'price_per_night' => (float) $this->price_per_night,
            'max_guests'      => $this->max_guests,
            'bedrooms'        => $this->bedrooms,
            'bathrooms'       => $this->bathrooms,
            'amenities'       => $this->amenities ?? [],
            'latitude'        => $this->latitude ? (float) $this->latitude : null,
            'longitude'       => $this->longitude ? (float) $this->longitude : null,
            'is_active'       => $this->is_active,
            'created_at'      => $this->created_at?->toISOString(),

            // Atributos calculados del modelo
            'average_rating'  => $this->average_rating,
            'reviews_count'   => $this->reviews_count ?? 0,

            // whenLoaded → SOLO si se cargó con with('host')
            'host' => new UserResource($this->whenLoaded('host')),

            'reviews' => ReviewResource::collection(
                $this->whenLoaded('reviews')
            ),

            // when() incluye el campo solo si la condición es true
            // Útil para incluir datos solo para ciertos usuarios
            'reservation_count' => $this->when(
                $request->user()?->id === $this->host_id,
                fn() => $this->reservations_count ?? 0
            ),
        ];
    }
}
