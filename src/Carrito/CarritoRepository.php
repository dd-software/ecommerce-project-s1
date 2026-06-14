<?php

declare(strict_types=1);

/**
 * CarritoService - Lógica de negocio del carrito de compras
 * REFACTORIZADO: 
 * - Inyecta CatalogoService (no hard-coded)
 * - Métodos privados para IVA y formatos
 * - Código más limpio y testeable
 */
namespace App\Carrito;

use App\Catalogo\CatalogoService;

class CarritoService
{
    private CarritoRepository $repository;
    private CatalogoService $catalogoService;

    public function __construct(CarritoRepository $repository, CatalogoService $catalogoService)
    {
        $this->repository = $repository;
        $this->catalogoService = $catalogoService;  // ✅ INYECTADO (no hard-coded)
    }

    /**
     * Obtiene o crea el carrito activo del usuario/visitante
     */
    public function obtenerCarrito(?int $userId, ?string $sessionId): array
    {
        $carrito = $this->obtenerOCrearCarritoBase($userId, $sessionId);

        // Obtener items y formatearlos
        if (isset($carrito['id'])) {
            $items = $this->repository->obtenerItems($carrito['id']);
            $carrito['items'] = $this->formatearItems($items);
        } else {
            $carrito['items'] = [];
        }

        // ✅ CAMBIO: Aplicar formatos (IVA, dinero) en método privado
        return $this->formatearCarrito($carrito);
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
        $producto = $this->catalogoService->obtenerProducto($productoId);
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
     * ============================================================================
     * MÉTODOS PRIVADOS - Lógica auxiliar
     * ============================================================================
     */

    /**
     * PRIVADO: Obtiene o crea un carrito base (sin items ni formatos)
     */
    private function obtenerOCrearCarritoBase(?int $userId, ?string $sessionId): array
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
            return ['id' => $carritoId];
        }

        return $carrito;
    }

    /**
     * PRIVADO: Obtiene o crea un carrito y retorna su ID
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
     * PRIVADO: Formatea items para respuesta API
     * Convierte tipos de datos y calcula subtotales
     */
    private function formatearItems(array $items): array
    {
        foreach ($items as &$item) {
            $item['id'] = (int)$item['id'];
            $item['producto_id'] = (int)($item['id_producto'] ?? $item['producto_id']);
            $item['cantidad'] = (int)$item['cantidad'];
            $item['precio_unitario'] = (int)($item['precio_unitario'] ?? 0);
            $item['subtotal'] = $item['precio_unitario'] * $item['cantidad'];
            
            // ✅ Usar método privado para formatear dinero
            $item['precio_formateado'] = $this->formatearDinero($item['precio_unitario']);
            $item['subtotal_formateado'] = $this->formatearDinero($item['subtotal']);
        }
        return $items;
    }

    /**
     * PRIVADO: Calcula el total del carrito (en centavos)
     */
    private function calcularTotal(array $items): int
    {
        return array_reduce($items, fn(int $sum, array $item) =>
            $sum + ((int)$item['precio_unitario'] * (int)$item['cantidad']), 0);
    }

    /**
     * ============================================================================
     * MÉTODOS PRIVADOS - Formateo (IVA y dinero)
     * ✅ REFACTOR: Métodos dedicados a lógica de presentación
     * ============================================================================
     */

    /**
     * PRIVADO: Formatea un monto en centavos a formato de dinero chileno
     * Ejemplo: 19900 → "$19.900"
     * 
     * @param int $centavos Monto en centavos
     * @return string Monto formateado con símbolo $ y separadores
     */
    private function formatearDinero(int $centavos): string
    {
        return '$' . number_format($centavos / 100, 0, ',', '.');
    }

    /**
     * PRIVADO: Calcula y formatea el IVA (19%)
     * Retorna array con valores en centavos y formateados
     * 
     * @param int $subtotal Subtotal en centavos
     * @return array ['iva' => int, 'iva_formateado' => string]
     */
    private function calcularYFormatearIVA(int $subtotal): array
    {
        // Calcular IVA al 19% (RN-B03 implícita)
        $iva = (int)round($subtotal * 0.19);

        return [
            'iva' => $iva,
            'iva_formateado' => $this->formatearDinero($iva),
        ];
    }

    /**
     * PRIVADO: Aplica formatos completos al carrito
     * - Calcula subtotal, IVA y total con IVA
     * - Formatea todos los montos en formato pesos chileno
     * 
     * ✅ REFACTOR: Toda la lógica de presentación centralizada aquí
     * 
     * @param array $carrito Carrito base con items
     * @return array Carrito formateado con totales y montos en pesos
     */
    private function formatearCarrito(array $carrito): array
    {
        // Calcular total desde items
        $carrito['total'] = $this->calcularTotal($carrito['items'] ?? []);
        $carrito['subtotal'] = $carrito['total'];

        // Calcular y formatear IVA
        $ivaData = $this->calcularYFormatearIVA($carrito['subtotal']);
        $carrito['iva'] = $ivaData['iva'];
        $carrito['iva_formateado'] = $ivaData['iva_formateado'];

        // Calcular total con IVA
        $carrito['total_con_iva'] = $carrito['subtotal'] + $carrito['iva'];

        // Formatear todos los montos en dinero
        $carrito['subtotal_formateado'] = $this->formatearDinero($carrito['subtotal']);
        $carrito['total_formateado'] = $this->formatearDinero($carrito['total_con_iva']);

        return $carrito;
    }
}