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
    // Inyección de dependencias — Laravel resuelve SpaceService automáticamente
    public function __construct(
        private SpaceService $spaceService
    ) {}

    // GET /api/spaces
    public function index(Request $request): AnonymousResourceCollection
    {
        // $request->query() equivale a req.query en Express
        $spaces = $this->spaceService->search($request->query());

        // SpaceResource::collection() aplica el resource a cada elemento
        return SpaceResource::collection($spaces);
    }

    // GET /api/spaces/{space}
    // Laravel resuelve automáticamente el modelo por su ID (Route Model Binding)
    // Si no existe → 404 automático
    public function show(int $id): SpaceResource
    {
        $space = $this->spaceService->findWithDetails($id);
        return new SpaceResource($space);
    }

    // POST /api/spaces
    public function store(StoreSpaceRequest $request): JsonResponse
    {
        // validated() devuelve solo los datos que pasaron la validación
        // Nunca uses $request->all() — podría incluir campos no esperados
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
        Space $space    // Route Model Binding — Laravel busca el espacio por ID
    ): SpaceResource {
        // authorize() verifica con la Policy que el usuario puede editar este espacio
        $this->authorize('update', $space);

        $updated = $this->spaceService->updateSpace(
            $space,
            $request->validated()
        );

        return new SpaceResource($updated);
    }

    // DELETE /api/spaces/{space}
    public function destroy(Space $space): JsonResponse
    {
        $this->authorize('delete', $space);
        $this->spaceService->deleteSpace($space);

        // 204 No Content — éxito sin cuerpo de respuesta
        return response()->json(null, 204);
    }
}
