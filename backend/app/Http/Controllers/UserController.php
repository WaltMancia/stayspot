<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // PUT /api/users/profile
    public function updateProfile(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();

        // update() con validated() — solo los campos validados
        $user->update($request->validated());

        return new UserResource($user->fresh());
    }

    // GET /api/users/{user}/spaces — espacios públicos de un host
    public function spaces(int $userId): JsonResponse
    {
        $user = \App\Models\User::findOrFail($userId);

        $spaces = $user->spaces()
            ->with(['reviews'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->active()
            ->latest()
            ->paginate(12);

        return response()->json($spaces);
    }
}
