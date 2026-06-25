<?php

declare(strict_types=1);

/**
 * ResenasService - Lógica de reseñas de productos
 */
namespace App\Resenas;

class ResenasService
{
    public function __construct(private ResenasRepository $repository) {}

    /** Reseñas + resumen (promedio/total) de un producto */
    public function obtenerDeProducto(int $productoId): array
    {
        return [
            'resenas'  => $this->repository->listarPorProducto($productoId),
            'resumen'  => $this->repository->resumen($productoId),
        ];
    }

    /**
     * Crea/actualiza la reseña del usuario. Devuelve el resumen actualizado.
     * @throws \InvalidArgumentException si calificación o producto inválidos
     */
    public function crear(int $productoId, int $userId, int $calificacion, ?string $comentario): array
    {
        if ($calificacion < 1 || $calificacion > 5) {
            throw new \InvalidArgumentException('La calificación debe estar entre 1 y 5.');
        }
        if (!$this->repository->productoExiste($productoId)) {
            throw new \InvalidArgumentException('El producto no existe.');
        }
        $comentario = $comentario !== null ? trim($comentario) : null;
        $this->repository->guardar($productoId, $userId, $calificacion, $comentario ?: null);
        return $this->repository->resumen($productoId);
    }
}
