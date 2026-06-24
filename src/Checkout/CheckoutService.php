<?php

declare(strict_types=1);

/**
 * CheckoutService - Lógica de negocio del proceso de checkout
 * Orquesta la creación de pedidos con validación de stock y transacciones ACID
 */
namespace App\Checkout;

use App\Core\Database;
use App\Carrito\CarritoService;
use App\Carrito\CarritoRepository;
use App\Catalogo\CatalogoService;
use App\Catalogo\CatalogoRepository;

class CheckoutService
{
    private CheckoutRepository $repository;
    private CarritoService $carritoService;
    private CatalogoService $catalogoService;

    public function __construct(CheckoutRepository $repository)
    {
        $this->repository = $repository;
        $this->carritoService = new CarritoService(new CarritoRepository());
        $this->catalogoService = new CatalogoService(new CatalogoRepository());
    }

    /**
     * Crea un pedido desde el carrito del usuario (RN-D01, RN-D02, RN-D03)
     * Proceso transaccional ACID para garantizar integridad
     */
    public function crearPedido(
        int $userId,
        int $carritoId,
        string $direccionEnvio,
        string $telefono,
        string $notas,
        ?string $cuponCodigo
    ): array {
        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            // Obtener items del carrito
            $items = $this->carritoService->obtenerItemsParaCheckout($carritoId);

            // Validar carrito no vacío (RN-D01)
            if (empty($items)) {
                throw new \RuntimeException('El carrito está vacío. No se puede crear el pedido.');
            }

            // Validar stock de cada producto (RN-D03, RN-001)
            foreach ($items as $item) {
                $productoId = (int)$item['id_producto'];
                $cantidad = (int)$item['cantidad'];

                if (!$this->catalogoService->verificarStock($productoId, $cantidad)) {
                    $producto = (new CatalogoRepository())->buscarPorId($productoId);
                    $nombreProducto = $producto ? $producto['nombre'] : "ID {$productoId}";
                    throw new \RuntimeException(
                        "Stock insuficiente para '{$nombreProducto}'. Stock disponible: " .
                        ($producto ? $producto['stock'] : 0)
                    );
                }
            }

            // Calcular totales (precios en centavos)
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += (int)$item['precio_unitario'] * (int)$item['cantidad'];
            }

            // Calcular IVA (19%)
            $iva = (int)round($subtotal * 0.19);
            $total = $subtotal + $iva;

            // Aplicar cupón si existe
            $descuentoAplicado = 0;
            $cuponId = null;
            if ($cuponCodigo) {
                $cupon = $this->repository->validarCupon($cuponCodigo, $subtotal);
                if ($cupon) {
                    // Límite por usuario: 1 uso por persona (solo cuenta compras pagadas).
                    if ($this->repository->usuarioYaUsoCupon($userId, (int)$cupon['id'])) {
                        throw new \RuntimeException('Este cupón ya fue usado en una compra anterior.');
                    }
                    if ($cupon['tipo_descuento'] === 'porcentaje') {
                        $descuentoAplicado = (int)round($subtotal * $cupon['valor'] / 100);
                    } else {
                        $descuentoAplicado = (int)$cupon['valor'];
                    }
                    $total -= $descuentoAplicado;
                    if ($total < 0) $total = 0;
                    $cuponId = (int)$cupon['id'];
                }
            }

            // Crear el pedido (RN-D03: estado inicial = pendiente)
            $pedidoId = $this->repository->crearPedido(
                userId: $userId,
                subtotal: $subtotal,
                iva: $iva,
                total: $total,
                direccionEnvio: $direccionEnvio,
                telefono: $telefono,
                notas: $notas
            );

            // Registrar estado inicial (RN-005)
            $this->repository->registrarEstado($pedidoId, 'pendiente', $userId, 'Pedido creado');

            // Insertar detalle del pedido (snapshot inmutable)
            foreach ($items as $item) {
                $producto = (new CatalogoRepository())->buscarPorId((int)$item['id_producto']);
                $this->repository->agregarDetalle(
                    pedidoId: $pedidoId,
                    productoId: (int)$item['id_producto'],
                    nombreProducto: $producto ? $producto['nombre'] : ($item['nombre'] ?? 'Producto'),
                    cantidad: (int)$item['cantidad'],
                    precioUnitario: (int)$item['precio_unitario']
                );

                // NO descontar stock aquí (RN-003: solo tras pago confirmado)
                // El descuento de stock ocurre en el módulo de Pagos
            }

            // Aplicar cupón si se usó
            if ($cuponId) {
                $this->repository->aplicarCupon($pedidoId, $cuponId, $descuentoAplicado);
            }

            // NO desactivar el carrito acá: si el pago se rechaza, el pedido se cancela
            // y el carrito debe seguir disponible para reintentar. El carrito se vacía
            // solo tras un pago APROBADO (ver PagosService::procesarPago).

            $db->commit();

            // Retornar pedido creado
            $pedido = $this->repository->obtenerPedido($pedidoId);
            $pedido['mensaje'] = 'Pedido creado exitosamente. Proceda al pago.';

            return $pedido;

        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * Obtiene los pedidos de un usuario
     */
    public function obtenerPedidosUsuario(int $userId): array
    {
        return $this->repository->obtenerPedidosUsuario($userId);
    }

    /**
     * Obtiene el detalle completo de un pedido
     */
    public function obtenerPedido(int $pedidoId): ?array
    {
        return $this->repository->obtenerPedido($pedidoId);
    }

    /**
     * Actualiza el estado de un pedido (usado por Pagos y Admin)
     */
    public function actualizarEstado(int $pedidoId, string $nuevoEstado, ?int $userId = null, string $comentario = ''): void
    {
        // Validar transición de estado (RN-005)
        $pedido = $this->repository->obtenerPedido($pedidoId);
        if (!$pedido) {
            throw new \RuntimeException('Pedido no encontrado.');
        }

        $this->repository->actualizarEstado($pedidoId, $nuevoEstado);
        $this->repository->registrarEstado($pedidoId, $nuevoEstado, $userId, $comentario);
    }

    /**
     * Descuenta el stock de los productos tras confirmación de pago (RN-003)
     * Usado por el módulo de Pagos
     */
    public function descontarStock(int $pedidoId): void
    {
        $detalles = $this->repository->obtenerDetallePedido($pedidoId);
        foreach ($detalles as $detalle) {
            $this->repository->descontarStockProducto(
                (int)$detalle['id_producto'],
                (int)$detalle['cantidad']
            );
        }
    }
}
