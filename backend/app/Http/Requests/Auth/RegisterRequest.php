<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    // En rutas públicas authorize() siempre es true
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|min:2|max:100',
            'email'    => 'required|email:rfc,dns|unique:users,email|max:150',
            // email:rfc,dns → valida formato RFC Y que el dominio existe en DNS
            // unique:users,email → verifica que no existe en la tabla users
            'password' => [
                'required',
                'confirmed',     // requiere campo password_confirmation
                Password::min(8) // mínimo 8 caracteres
                    ->letters()  // al menos una letra
                    ->numbers(), // al menos un número
            ],
            'role'     => 'nullable|in:guest,host',
            'phone'    => 'nullable|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'             => 'Este correo ya está registrado',
            'email.email'              => 'El formato del correo no es válido',
            'password.confirmed'       => 'Las contraseñas no coinciden',
            'password.min'             => 'La contraseña debe tener al menos 8 caracteres',
            'role.in'                  => 'El rol debe ser guest o host',
        ];
    }
}
