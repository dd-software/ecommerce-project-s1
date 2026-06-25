<?php

declare(strict_types=1);

/**
 * FavoritosController - Endpoints de la lista de deseos (requieren auth)
 */
namespace App\Favoritos;

use App\Core\{Request, Response};

class FavoritosController
{
    private FavoritosService $service;

    public function __construct()
    {
        $this->service = new FavoritosService(new FavoritosRepository());
    }

    /** GET /api/favoritos */
    public function listar(Request $request, Response $response, array $params): void
    {
        try {
            $userId = (int)$request->getAttribute('authenticated_user')['id'];
            $response->json([
                'productos' => $this->service->listar($userId),
                'ids'       => $this->service->ids($userId),
            ]);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al obtener favoritos.', 500);
        }
    }

    /** POST /api/favoritos */
    public function agregar(Request $request, Response $response, array $params): void
    {
        try {
            $request->validateRequired(['producto_id']);
            $userId = (int)$request->getAttribute('authenticated_user')['id'];
            $this->service->agregar($userId, (int)$request->getBody('producto_id'));
            $response->json(['ok' => true], 201);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al agregar a favoritos.', 500);
        }
    }

    /** DELETE /api/favoritos/{id}  (id = producto_id) */
    public function eliminar(Request $request, Response $response, array $params): void
    {
        try {
            $userId = (int)$request->getAttribute('authenticated_user')['id'];
            $this->service->eliminar($userId, (int)$params['id']);
            $response->json(['ok' => true]);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al quitar de favoritos.', 500);
        }
    }
}
