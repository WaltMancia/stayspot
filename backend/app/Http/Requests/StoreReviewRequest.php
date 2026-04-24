<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reservation = $this->route('reservation');

        // Solo el huésped de la reserva puede dejar reseña
        // Y solo si la reserva está completada y no tiene reseña aún
        return auth()->check()
            && $reservation->guest_id === auth()->id()
            && $reservation->canBeReviewed();
    }

    public function rules(): array
    {
        return [
            'rating'  => 'required|integer|between:1,5',
            'comment' => 'nullable|string|min:10|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'rating.between' => 'La calificación debe ser entre 1 y 5',
            'comment.min'    => 'El comentario debe tener al menos 10 caracteres',
        ];
    }
}
