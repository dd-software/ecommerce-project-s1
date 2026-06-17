<?php

declare(strict_types=1);

/**
 * CarritoService - Lógica de negocio del carrito de compras
 */
namespace App\Carrito;

use App\Catalogo\CatalogoService;
use App\Catalogo\CatalogoRepository;

class CarritoService
{
    private CarritoRepository $repository;
    private CatalogoService $catalogoService;

    public function __construct(CarritoRepository $repository)
    {
        $this->repository = $repository;
        $this->catalogoService = new CatalogoService(new CatalogoRepository());
    }

    /**
     * Obtiene o crea el carrito activo del usuario/visitante
     */
    public function obtenerCarrito(?int $userId, ?string $sessionId): array
    {
        $carrito = null;

        if ($userId !== null) {
            $carrito = $this->repository->obtenerCarritoActivoPorUsuario($userId);
        }

        if (!$carrito && $sessionId !== null) {
            $carrito = $this->repository->obtenerCarritoActivoPorSession($sessionId);
        }

        if (!$carrito) {
            $carritoId = $this->repository->crearCarrito($userId, $sessionId);
            $carrito = ['id' => $carritoId, 'items' => [], 'total' => 0];
        } else {
            $items = $this->repository->obtenerItems($carrito['id']);
            $carrito['items'] = $this->formatearItems($items);
            $carrito['total'] = $this->calcularTotal($carrito['items']);
        }

        // Calcular IVA (19%)
        $carrito['subtotal'] = $carrito['total'];
        $carrito['iva'] = (int)round($carrito['subtotal'] * 0.19);
        $carrito['total_con_iva'] = $carrito['subtotal'] + $carrito['iva'];

        // Formatear montos
        $carrito['subtotal_formateado'] = '$' . number_format($carrito['subtotal'] / 100, 0, ',', '.');
        $carrito['iva_formateado'] = '$' . number_format($carrito['iva'] / 100, 0, ',', '.');
        $carrito['total_formateado'] = '$' . number_format($carrito['total_con_iva'] / 100, 0, ',', '.');

        return $carrito;
    }

    /**
     * Agrega un producto al carrito
     * @throws \RuntimeException si no hay stock suficiente (RN-B01)
     */
    public function agregarItem(int $productoId, int $cantidad, ?int $userId, ?string $sessionId): void
    {
        // Verificar stock (RN-B01)
        if (!$this->catalogoService->verificarStock($productoId, $cantidad)) {
            throw new \RuntimeException(
                'Lo sentimos, no hay stock suficiente para procesar la cantidad solicitada.'
            );
        }

        // Obtener o crear carrito
        $carritoId = $this->obtenerOCrearCarritoId($userId, $sessionId);

        // Obtener precio actual del producto
        $producto = (new CatalogoRepository())->buscarPorId($productoId);
        if (!$producto) {
            throw new \InvalidArgumentException('Producto no encontrado.');
        }

        // Agregar o actualizar item
        $itemExistente = $this->repository->buscarItem($carritoId, $productoId);

        if ($itemExistente) {
            $nuevaCantidad = (int)$itemExistente['cantidad'] + $cantidad;
            if (!$this->catalogoService->verificarStock($productoId, $nuevaCantidad)) {
                throw new \RuntimeException(
                    'Stock insuficiente. Ya tienes ' . $itemExistente['cantidad'] . ' en el carrito.'
                );
            }
            $this->repository->actualizarItem($itemExistente['id'], $nuevaCantidad);
        } else {
            $this->repository->agregarItem($carritoId, $productoId, $cantidad, (int)$producto['precio']);
        }
    }

    /**
     * Actualiza la cantidad de un item
     */
    public function actualizarCantidad(int $itemId, int $cantidad): void
    {
        if ($cantidad < 1 || $cantidad > 999) {
            throw new \InvalidArgumentException('La cantidad debe estar entre 1 y 999.');
        }

        $item = $this->repository->buscarItemPorId($itemId);
        if (!$item) {
            throw new \InvalidArgumentException('Item no encontrado en el carrito.');
        }

        // Verificar stock
        if (!$this->catalogoService->verificarStock((int)$item['id_producto'], $cantidad)) {
            throw new \RuntimeException('Stock insuficiente para la cantidad solicitada.');
        }

        $this->repository->actualizarItem($itemId, $cantidad);
    }

    /**
     * Elimina un item del carrito
     */
    public function eliminarItem(int $itemId): void
    {
        $this->repository->eliminarItem($itemId);
    }

    /**
     * Vacía el carrito completo
     */
    public function vaciarCarrito(?int $userId, ?string $sessionId): void
    {
        $carritoId = $this->obtenerOCrearCarritoId($userId, $sessionId);
        $this->repository->vaciarCarrito($carritoId);
    }

    /**
     * Sincroniza carrito de visitante al hacer login (RN-B02)
     */
    public function sincronizarCarrito(int $userId, string $sessionId): void
    {
        $carritoVisitante = $this->repository->obtenerCarritoActivoPorSession($sessionId);
        if (!$carritoVisitante) {
            return;
        }

        $carritoUsuario = $this->repository->obtenerCarritoActivoPorUsuario($userId);

        if (!$carritoUsuario) {
            // Asignar carrito visitante al usuario
            $this->repository->asignarCarritoAUsuario($carritoVisitante['id'], $userId);
        } else {
            // Merge: mover items del visitante al carrito del usuario
            $itemsVisitante = $this->repository->obtenerItems($carritoVisitante['id']);
            foreach ($itemsVisitante as $item) {
                $existente = $this->repository->buscarItem(
                    $carritoUsuario['id'],
                    (int)$item['id_producto']
                );
                if ($existente) {
                    $nuevaCantidad = (int)$existente['cantidad'] + (int)$item['cantidad'];
                    $this->repository->actualizarItem((int)$existente['id'], $nuevaCantidad);
                } else {
                    $this->repository->agregarItem(
                        $carritoUsuario['id'],
                        (int)$item['id_producto'],
                        (int)$item['cantidad'],
                        (int)$item['precio_unitario']
                    );
                }
            }
            // Desactivar carrito visitante
            $this->repository->desactivarCarrito($carritoVisitante['id']);
        }
    }

    /**
     * Obtiene los items del carrito para checkout
     */
    public function obtenerItemsParaCheckout(int $carritoId): array
    {
        return $this->repository->obtenerItems($carritoId);
    }

    /**
     * Obtiene o crea un carrito y retorna su ID
     */
    private function obtenerOCrearCarritoId(?int $userId, ?string $sessionId): int
    {
        $carrito = null;
        if ($userId !== null) {
            $carrito = $this->repository->obtenerCarritoActivoPorUsuario($userId);
        }
        if (!$carrito && $sessionId !== null) {
            $carrito = $this->repository->obtenerCarritoActivoPorSession($sessionId);
        }
        if (!$carrito) {
            return $this->repository->crearCarrito($userId, $sessionId);
        }
        return (int)$carrito['id'];
    }

    /**
     * Formatea items para respuesta API
     */
    private function formatearItems(array $items): array
    {
        foreach ($items as &$item) {
            $item['id'] = (int)$item['id'];
            $item['producto_id'] = (int)($item['id_producto'] ?? $item['producto_id']);
            $item['cantidad'] = (int)$item['cantidad'];
            $item['precio_unitario'] = (int)($item['precio_unitario'] ?? 0);
            $item['subtotal'] = $item['precio_unitario'] * $item['cantidad'];
            $item['precio_formateado'] = '$' . number_format($item['precio_unitario'] / 100, 0, ',', '.');
            $item['subtotal_formateado'] = '$' . number_format($item['subtotal'] / 100, 0, ',', '.');
        }
        return $items;
    }

    /**
     * Calcula el total del carrito (centavos)
     */
    private function calcularTotal(array $items): int
    {
        return array_reduce($items, fn(int $sum, array $item) =>
            $sum + ((int)$item['precio_unitario'] * (int)$item['cantidad']), 0);
    }
}
