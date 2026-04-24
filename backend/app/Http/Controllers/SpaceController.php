<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSpaceRequest;
use App\Http\Requests\UpdateSpaceRequest;
use App\Http\Resources\SpaceResource;
use App\Models\Space;
use App\Services\SpaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SpaceController extends Controller
{
    public function __construct(
        private SpaceService $spaceService
    ) {}

    // GET /api/spaces
    public function index(Request $request): AnonymousResourceCollection
    {
        $spaces = $this->spaceService->search($request->query());
        return SpaceResource::collection($spaces);
    }

    // GET /api/spaces/{space}
    public function show(int $id): SpaceResource
    {
        $space = $this->spaceService->findWithDetails($id);
        return new SpaceResource($space);
    }

    // POST /api/spaces
    public function store(StoreSpaceRequest $request): JsonResponse
    {
        $space = $this->spaceService->createSpace(
            $request->validated(),
            $request->user()
        );

        return (new SpaceResource($space))
            ->response()
            ->setStatusCode(201);
    }

    // PUT /api/spaces/{space}
    public function update(
        UpdateSpaceRequest $request,
        Space $space
    ): SpaceResource {
        // La autorización ya fue verificada en UpdateSpaceRequest::authorize()
        // No necesitamos llamar a $this->authorize() aquí
        $updated = $this->spaceService->updateSpace(
            $space,
            $request->validated()
        );

        return new SpaceResource($updated);
    }

    // DELETE /api/spaces/{space}
    public function destroy(Space $space): JsonResponse
    {
        // Verificamos manualmente con la Policy
        $this->authorize('delete', $space);

        $this->spaceService->deleteSpace($space);

        return response()->json([
            'message' => 'Espacio eliminado correctamente',
        ], 200);
    }

    // GET /api/spaces/my-spaces — espacios del host autenticado
    public function mySpaces(Request $request): AnonymousResourceCollection
    {
        $spaces = Space::query()
            ->where('host_id', $request->user()->id)
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->withCount([
                'reservations',
                'reservations as pending_reservations_count'
                => fn($q) => $q->where('status', 'pending'),
            ])
            ->latest()
            ->paginate(12);

        return SpaceResource::collection($spaces);
    }

    // GET /api/spaces/stats — estadísticas del host
    public function stats(Request $request): JsonResponse
    {
        $stats = $this->spaceService->getHostStats($request->user());
        return response()->json($stats);
    }
}
