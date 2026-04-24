<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSpaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Verificamos que el usuario es el host del espacio
        // $this->route('space') obtiene el modelo del route binding
        $space = $this->route('space');
        return auth()->check()
            && ($space->belongsToHost(auth()->id()) || auth()->user()->isAdmin());
    }

    public function rules(): array
    {
        return [
            // sometimes → solo valida si el campo está presente en el request
            // Permite actualización parcial (PATCH semántico con PUT)
            'name'            => 'sometimes|string|min:5|max:150',
            'description'     => 'sometimes|nullable|string|max:3000',
            'city'            => 'sometimes|string|max:100',
            'address'         => 'sometimes|nullable|string|max:255',
            'price_per_night' => 'sometimes|numeric|min:1|max:50000',
            'max_guests'      => 'sometimes|integer|min:1|max:20',
            'bedrooms'        => 'sometimes|integer|min:1|max:20',
            'bathrooms'       => 'sometimes|integer|min:1|max:10',
            'amenities'       => 'sometimes|nullable|array',
            'amenities.*'     => 'string|max:50',
            'latitude'        => 'sometimes|nullable|numeric|between:-90,90',
            'longitude'       => 'sometimes|nullable|numeric|between:-180,180',
        ];
    }

    protected function failedAuthorization(): void
    {
        // Mensaje específico cuando la autorización falla
        throw new \Illuminate\Auth\Access\AuthorizationException(
            'No tienes permiso para editar este espacio.'
        );
    }
}