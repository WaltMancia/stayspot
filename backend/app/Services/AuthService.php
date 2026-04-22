<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $data): array
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'], // el cast 'hashed' lo hashea automáticamente
            'role'     => $data['role'] ?? 'guest',
            'phone'    => $data['phone'] ?? null,
        ]);

        return $this->buildTokenResponse($user, 'auth-token');
    }

    public function login(
        array $credentials,
        ?string $deviceName = null,
        ?string $ip = null
    ): array {
        $user = User::where('email', $credentials['email'])->first();

        // Verificamos credenciales — mismo mensaje para email y contraseña
        // incorrectos (no revelamos cuál de los dos falló)
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            // ValidationException genera automáticamente una respuesta 422
            // con el formato estándar de Laravel
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Tu cuenta ha sido desactivada.'],
            ]);
        }

        // Nombre descriptivo del token — identifica el dispositivo
        $tokenName = $deviceName
            ? "login|{$ip}|{$deviceName}"
            : 'auth-token';

        return $this->buildTokenResponse($user, $tokenName);
    }

    public function refreshToken(User $user): array
    {
        // Revocamos el token actual y generamos uno nuevo
        // Esto implementa la rotación de tokens
        $token = $user->currentAccessToken();
        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $token->delete();
        }

        return $this->buildTokenResponse($user, 'refreshed-token');
    }

    private function buildTokenResponse(User $user, string $tokenName): array
    {
        // createToken genera el token de Sanctum
        // El segundo parámetro son los "abilities" — permisos del token
        // Similar a los scopes de OAuth
        $tokenResult = $user->createToken($tokenName, ['*'], now()->addDays(7));

        return [
            'user'         => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
                'phone' => $user->phone,
            ],
            // plainTextToken es el token que enviamos al cliente
            // Solo está disponible UNA VEZ — en esta respuesta
            'access_token' => $tokenResult->plainTextToken,
            'token_type'   => 'Bearer',
            'expires_at'   => $tokenResult->accessToken->expires_at?->toISOString(),
        ];
    }
}
