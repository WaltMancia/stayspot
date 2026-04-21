<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// FormRequest combina autorización y validación
// Se ejecuta ANTES de que llegue al controller
// Si falla autorización → 403
// Si falla validación → 422 con errores detallados
class StoreSpaceRequest extends FormRequest
{
    // authorize() determina si el usuario puede hacer esta petición
    public function authorize(): bool
    {
        // Usamos el helper auth() para obtener el usuario autenticado
        return $this->user() && in_array($this->user()->role, ['host', 'admin']);
    }

    public function rules(): array
    {
        return [
            'name'            => 'required|string|min:5|max:150',
            'description'     => 'nullable|string|max:3000',
            'city'            => 'required|string|max:100',
            'address'         => 'nullable|string|max:255',
            'price_per_night' => 'required|numeric|min:1|max:50000',
            'max_guests'      => 'required|integer|min:1|max:20',
            'bedrooms'        => 'required|integer|min:1|max:20',
            'bathrooms'       => 'required|integer|min:1|max:10',
            // array → debe ser un array
            // array.* → valida cada elemento del array
            'amenities'       => 'nullable|array',
            'amenities.*'     => 'string|max:50',
            // nullable con regex para coordenadas
            'latitude'        => 'nullable|numeric|between:-90,90',
            'longitude'       => 'nullable|numeric|between:-180,180',
        ];
    }

    // Mensajes personalizados en español
    public function messages(): array
    {
        return [
            'name.required'            => 'El nombre del espacio es obligatorio',
            'name.min'                 => 'El nombre debe tener al menos 5 caracteres',
            'price_per_night.required' => 'El precio por noche es obligatorio',
            'price_per_night.min'      => 'El precio mínimo es $1 por noche',
            'price_per_night.max'      => 'El precio máximo es $50,000 por noche',
            'max_guests.required'      => 'La capacidad máxima de huéspedes es obligatoria',
        ];
    }

    // prepareForValidation() permite modificar los datos antes de validar
    // Útil para limpiar o normalizar el input
    protected function prepareForValidation(): void
    {
        if ($this->has('price_per_night')) {
            // Convertimos a float antes de validar
            $this->merge([
                'price_per_night' => (float) $this->price_per_night,
            ]);
        }
    }
}
