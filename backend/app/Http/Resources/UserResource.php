<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// JsonResource transforma un modelo Eloquent a array/JSON
// Es el DTO de respuesta — controla qué datos salen de la API
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'role'       => $this->role,
            'phone'      => $this->phone,
            'avatar'     => $this->avatar,
            'is_active'  => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),

            // whenLoaded → incluye la relación SOLO si fue cargada con with()
            // Evita queries adicionales y N+1 en el resource
            'spaces' => SpaceResource::collection(
                $this->whenLoaded('spaces')
            ),
        ];
    }
}
