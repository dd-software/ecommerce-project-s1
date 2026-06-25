<?php

declare(strict_types=1);

/**
 * FavoritosService - Lógica de la lista de deseos
 */
namespace App\Favoritos;

class FavoritosService
{
    public function __construct(private FavoritosRepository $repository) {}

    public function listar(int $userId): array
    {
        return $this->repository->listar($userId);
    }

    public function ids(int $userId): array
    {
        return $this->repository->ids($userId);
    }

    public function agregar(int $userId, int $productoId): void
    {
        $this->repository->agregar($userId, $productoId);
    }

    public function eliminar(int $userId, int $productoId): void
    {
        $this->repository->eliminar($userId, $productoId);
    }
}
