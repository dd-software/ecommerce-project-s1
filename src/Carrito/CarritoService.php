<?php

declare(strict_types=1);

namespace App\Carrito;

use App\Catalogo\CatalogoService;

class CarritoService
{
    private CarritoRepository $repository;
    private CatalogoService $catalogoService;

    public function __construct(CarritoRepository $repository, CatalogoService $catalogoService)
    {
        $this->repository = $repository;
        $this->catalogoService = $catalogoService;
    }

    public function obtenerCarrito(?int $userId = null, ?string $sessionId = null): array
    {
        $carrito = $this->obtenerCarritoActivo($userId, $sessionId);

        if (!$carrito) {
            return [
                'id' => null,
                'user_id' => $userId,
                'session_id' => $sessionId,
                'items' => [],
                'subtotal' => 0,
                'subtotal_formateado' => '$0',
                'iva' => 0,
                'iva_formateado' => '$0',
                'total' => 0,
                'total_formateado' => '$0',
            ];
        }

        $items = $this->repository->obtenerItems((int)$carrito['id']);
        $subtotal = 0;

        foreach ($items as &$item) {
            $item['cantidad'] = (int)$item['cantidad'];
            $item['precio_unitario'] = (int)$item['precio_unitario'];
            $item['total'] = $item['cantidad'] * $item['precio_unitario'];
            $item['precio_formateado'] = '$' . number_format($item['precio_unitario'] / 100, 0, ',', '.');
            $item['subtotal'] = $item['total']; // Compatibilidad con frontend
            $item['subtotal_formateado'] = '$' . number_format($item['total'] / 100, 0, ',', '.');
            $item['sin_stock'] = isset($item['stock']) && (int)$item['stock'] <= 0;
            $subtotal += $item['total'];
        }
        unset($item);

        $iva = (int)round($subtotal * 0.19);
        $total = $subtotal + $iva;

        $subtotalFormateado = '$' . number_format($subtotal / 100, 0, ',', '.');
        $ivaFormateado = '$' . number_format($iva / 100, 0, ',', '.');
        $totalFormateado = '$' . number_format($total / 100, 0, ',', '.');

        return [
            'id' => (int)$carrito['id'],
            'user_id' => isset($carrito['id_usuario']) ? (int)$carrito['id_usuario'] : null,
            'session_id' => $carrito['session_id'] ?? null,
            'items' => $items,
            'subtotal' => $subtotal,
            'subtotal_formateado' => $subtotalFormateado,
            'iva' => $iva,
            'iva_formateado' => $ivaFormateado,
            'total' => $total,
            'total_formateado' => $totalFormateado,
        ];
    }

    public function agregarItem(int $productoId, int $cantidad, ?int $userId = null, ?string $sessionId = null): void
    {
        if ($cantidad < 1) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor a cero.');
        }

        $producto = $this->catalogoService->obtenerProducto($productoId);
        if (!$producto) {
            throw new \RuntimeException('Producto no encontrado.');
        }

        if ($producto['stock'] < $cantidad) {
            throw new \RuntimeException('Stock insuficiente para el producto solicitado.');
        }

        $carrito = $this->getOrCreateCarrito($userId, $sessionId);
        $item = $this->repository->buscarItem((int)$carrito['id'], $productoId);

        if ($item) {
            $nuevoCantidad = (int)$item['cantidad'] + $cantidad;
            if ($nuevoCantidad > $producto['stock']) {
                throw new \RuntimeException('No hay stock suficiente para la cantidad solicitada.');
            }
            $this->repository->actualizarItem((int)$item['id'], $nuevoCantidad);
        } else {
            $this->repository->agregarItem(
                (int)$carrito['id'],
                $productoId,
                $cantidad,
                (int)$producto['precio']
            );
        }
    }

    public function actualizarCantidad(int $itemId, int $cantidad): void
    {
        if ($cantidad < 0) {
            throw new \InvalidArgumentException('La cantidad no puede ser negativa.');
        }

        $item = $this->repository->buscarItemPorId($itemId);
        if (!$item) {
            throw new \RuntimeException('Item de carrito no encontrado.');
        }

        if ($cantidad === 0) {
            $this->repository->eliminarItem($itemId);
            return;
        }

        $producto = $this->catalogoService->obtenerProducto((int)$item['id_producto']);
        if (!$producto) {
            throw new \RuntimeException('Producto no disponible.');
        }

        if ($cantidad > $producto['stock']) {
            throw new \RuntimeException('Stock insuficiente para la cantidad solicitada.');
        }

        $this->repository->actualizarItem($itemId, $cantidad);
    }

    public function eliminarItem(int $itemId): void
    {
        $this->repository->eliminarItem($itemId);
    }

    public function vaciarCarrito(int $carritoId): void
    {
        $this->repository->vaciarCarrito($carritoId);
    }

    public function sincronizarCarrito(int $userId, string $sessionId): void
    {
        $sessionCart = $this->repository->obtenerCarritoActivoPorSession($sessionId);
        if (!$sessionCart) {
            return;
        }

        $userCart = $this->repository->obtenerCarritoActivoPorUsuario($userId);
        if (!$userCart) {
            $this->repository->asignarCarritoAUsuario((int)$sessionCart['id'], $userId);
            return;
        }

        if ((int)$sessionCart['id'] === (int)$userCart['id']) {
            return;
        }

        $sessionItems = $this->repository->obtenerItems((int)$sessionCart['id']);
        foreach ($sessionItems as $sessionItem) {
            $productoId = (int)$sessionItem['id_producto'];
            $cantidad = (int)$sessionItem['cantidad'];
            $producto = $this->catalogoService->obtenerProducto($productoId);
            $stockDisponible = $producto ? (int)$producto['stock'] : 0;
            $cantidad = min($cantidad, $stockDisponible);

            if ($cantidad <= 0) {
                continue;
            }

            $existingItem = $this->repository->buscarItem((int)$userCart['id'], $productoId);
            if ($existingItem) {
                $nuevaCantidad = min((int)$existingItem['cantidad'] + $cantidad, $stockDisponible);
                $this->repository->actualizarItem((int)$existingItem['id'], $nuevaCantidad);
            } else {
                $this->repository->agregarItem(
                    (int)$userCart['id'],
                    $productoId,
                    $cantidad,
                    (int)$sessionItem['precio_unitario']
                );
            }
        }

        $this->repository->desactivarCarrito((int)$sessionCart['id']);
    }

    public function obtenerItemsParaCheckout(int $carritoId): array
    {
        return $this->repository->obtenerItems($carritoId);
    }

    private function obtenerCarritoActivo(?int $userId, ?string $sessionId): ?array
    {
        if ($userId !== null) {
            $carrito = $this->repository->obtenerCarritoActivoPorUsuario($userId);
            if ($carrito) {
                return $carrito;
            }
        }

        if ($sessionId !== null) {
            return $this->repository->obtenerCarritoActivoPorSession($sessionId);
        }

        return null;
    }

    private function getOrCreateCarrito(?int $userId, ?string $sessionId): array
    {
        $carrito = $this->obtenerCarritoActivo($userId, $sessionId);
        if ($carrito) {
            return $carrito;
        }

        $carritoId = $this->repository->crearCarrito($userId, $sessionId);
        return [
            'id' => $carritoId,
            'id_usuario' => $userId,
            'session_id' => $sessionId,
        ];
    }
}
