<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->check();
    }

    public function rules(): array
    {
        return [
            'name'  => 'sometimes|string|min:2|max:100',
            // sometimes → solo valida si el campo está presente
            // ignore el email actual del usuario para no fallar unique
            'email' => [
                'sometimes',
                'email:rfc',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],
            'phone'  => 'sometimes|nullable|string|max:20',
            'avatar' => 'sometimes|nullable|url|max:500',
        ];
    }
}
