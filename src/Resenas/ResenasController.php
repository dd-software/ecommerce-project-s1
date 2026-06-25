<?php

declare(strict_types=1);

/**
 * ResenasController - Endpoints de reseñas de productos
 */
namespace App\Resenas;

use App\Core\{Request, Response};

class ResenasController
{
    private ResenasService $service;

    public function __construct()
    {
        $this->service = new ResenasService(new ResenasRepository());
    }

    /** GET /api/catalogo/{id}/resenas */
    public function listar(Request $request, Response $response, array $params): void
    {
        try {
            $response->json($this->service->obtenerDeProducto((int)$params['id']));
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al obtener reseñas.', 500);
        }
    }

    /** POST /api/catalogo/{id}/resenas (auth) */
    public function crear(Request $request, Response $response, array $params): void
    {
        try {
            $request->validateRequired(['calificacion']);
            $user = $request->getAttribute('authenticated_user');

            $resumen = $this->service->crear(
                productoId: (int)$params['id'],
                userId: (int)$user['id'],
                calificacion: (int)$request->getBody('calificacion'),
                comentario: $request->getBody('comentario')
            );

            $response->json($resumen, 201);
        } catch (\InvalidArgumentException $e) {
            $response->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al guardar la reseña.', 500);
        }
    }
}
