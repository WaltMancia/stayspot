<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    // POST /api/auth/register
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json($result, 201);
    }

    // POST /api/auth/login
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->validated(),
            $request->userAgent(),   // guarda el dispositivo que hizo login
            $request->ip()
        );

        return response()->json($result);
    }

    // POST /api/auth/logout
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoca el token actual si la petición viene con bearer token.
        $user?->currentAccessToken()?->delete();

        // Si la autenticación vino por sesión/Sanctum SPA, invalidamos la sesión.
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'message' => 'Sesión cerrada exitosamente',
        ]);
    }

    // POST /api/auth/logout-all
    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoca TODOS los tokens del usuario.
        // "Cerrar sesión en todos los dispositivos"
        $user?->tokens()->delete();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'message' => 'Sesión cerrada en todos los dispositivos',
        ]);
    }

    // GET /api/auth/me
    public function me(Request $request): UserResource
    {
        // El usuario autenticado viene del token de Sanctum
        return new UserResource($request->user()->load('spaces'));
    }

    // POST /api/auth/refresh
    public function refresh(Request $request): JsonResponse
    {
        return response()->json(
            $this->authService->refreshToken($request->user())
        );
    }
}
