<?php

declare(strict_types=1);

/**
 * InventarioService - Gestión de stock y movimientos
 */
namespace App\Inventario;

use App\Core\Database;

class InventarioService
{
    private InventarioRepository $repository;

    public function __construct(InventarioRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Verifica disponibilidad de stock para múltiples productos
     */
    public function verificarDisponibilidad(array $productoIds): array
    {
        $resultado = [];
        foreach ($productoIds as $id) {
            $stock = $this->repository->obtenerStock($id);
            $resultado[] = [
                'producto_id'  => $id,
                'disponible'   => $stock > 0,
                'stock_actual' => $stock,
            ];
        }
        return $resultado;
    }

    /**
     * Registra un egreso de inventario (usado por Pagos tras confirmación)
     */
    public function registrarEgreso(int $productoId, int $cantidad, int $pedidoId, string $motivo): void
    {
        $stockAnterior = $this->repository->obtenerStock($productoId);

        $this->repository->registrarMovimiento(
            productoId: $productoId,
            cantidad: -$cantidad,
            tipo: 'egreso',
            motivo: $motivo,
            pedidoId: $pedidoId,
            stockAnterior: $stockAnterior,
            stockNuevo: $stockAnterior - $cantidad
        );
    }

    /**
     * Lista movimientos de inventario con paginación
     */
    public function listarMovimientos(?int $productoId, int $pagina, int $porPagina): array
    {
        return $this->repository->listarMovimientos($productoId, $pagina, $porPagina);
    }

    /**
     * Obtiene productos con stock bajo el mínimo (alertas)
     */
    public function obtenerAlertasStock(): array
    {
        return $this->repository->obtenerAlertasStock();
    }

    /**
     * Ajusta el stock manualmente (entrada/ajuste)
     */
    public function ajustarStock(int $productoId, int $cantidad, string $tipo, string $motivo, int $userId): void
    {
        $stockAnterior = $this->repository->obtenerStock($productoId);

        if ($tipo === 'entrada') {
            $nuevoStock = $stockAnterior + $cantidad;
            $cantidadMov = $cantidad;
        } elseif ($tipo === 'ajuste') {
            $nuevoStock = $cantidad; // cantidad es el nuevo valor absoluto
            $cantidadMov = $nuevoStock - $stockAnterior;
        } else {
            throw new \InvalidArgumentException('Tipo de movimiento inválido.');
        }

        if ($nuevoStock < 0) {
            throw new \RuntimeException('El stock no puede ser negativo.');
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // Actualizar stock en tabla productos
            $this->repository->actualizarStock($productoId, $nuevoStock);

            // Registrar movimiento
            $this->repository->registrarMovimiento(
                productoId: $productoId,
                cantidad: $cantidadMov,
                tipo: $tipo,
                motivo: $motivo,
                pedidoId: null,
                stockAnterior: $stockAnterior,
                stockNuevo: $nuevoStock,
                userId: $userId
            );

            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
}
