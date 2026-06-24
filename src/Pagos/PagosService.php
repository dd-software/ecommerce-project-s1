<?php

declare(strict_types=1);

/**
 * PagosService - Lógica de pagos.
 * Real con MercadoPago (Checkout Pro, redirección + webhook). Si no hay credenciales
 * (MP_ACCESS_TOKEN vacío/placeholder), cae a un pago SIMULADO para no romper dev/local.
 */
namespace App\Pagos;

use App\Core\Database;
use App\Checkout\CheckoutService;
use App\Checkout\CheckoutRepository;
use App\Inventario\InventarioService;
use App\Inventario\InventarioRepository;
use App\Carrito\CarritoRepository;

class PagosService
{
    private PagosRepository $repository;
    private CheckoutService $checkoutService;

    public function __construct(PagosRepository $repository)
    {
        $this->repository = $repository;
        $this->checkoutService = new CheckoutService(new CheckoutRepository());
    }

    /**
     * Inicia el pago de un pedido pendiente.
     * - MercadoPago activo  -> crea preferencia y devuelve init_point (URL para redirigir).
     * - MercadoPago inactivo -> aprueba simulado al instante (dev).
     */
    public function iniciarPago(int $pedidoId, int $userId, string $payerEmail): array
    {
        $pedido = $this->checkoutService->obtenerPedido($pedidoId);
        if (!$pedido) {
            throw new \RuntimeException('Pedido no encontrado.');
        }
        if ($pedido['estado'] !== 'pendiente') {
            throw new \RuntimeException('El pedido ya fue procesado. Estado actual: ' . $pedido['estado']);
        }

        $monto = (int)$pedido['total'];

        if (!MercadoPago::habilitado()) {
            return $this->procesarSimulado($pedidoId, $userId, $monto);
        }

        // MercadoPago: una sola línea con el total del pedido (incluye IVA/descuento ya
        // calculados por el checkout). Evita descuadres entre ítems y total.
        $items = [[
            'title'       => 'Pedido QuadCore #' . $pedidoId,
            'quantity'    => 1,
            'unit_price'  => $monto,
            'currency_id' => 'CLP',
        ]];

        $pref = MercadoPago::crearPreferencia($items, $pedidoId, $payerEmail);

        // Registrar el pago como pendiente, guardando la preferencia como referencia.
        $this->repository->registrarPago(
            pedidoId: $pedidoId,
            metodoPago: 'mercadopago',
            monto: $monto,
            referenciaExterna: $pref['preference_id'],
            estado: 'pendiente',
            respuesta: json_encode($pref)
        );

        return [
            'modo'       => 'mercadopago',
            'init_point' => $pref['init_point'],
            'pedido_id'  => $pedidoId,
        ];
    }

    /**
     * Confirma un pago consultando a MercadoPago por el ID de pago (fuente de verdad).
     * Idempotente: si el pedido ya no está pendiente, no hace nada.
     * La usan tanto el webhook como el retorno del usuario.
     */
    public function confirmarPagoMercadoPago(string $paymentId): array
    {
        $pago = MercadoPago::obtenerPago($paymentId);

        $pedidoId = (int)($pago['external_reference'] ?? 0);
        $estado   = (string)($pago['status'] ?? '');   // approved | rejected | pending | ...
        if ($pedidoId <= 0) {
            throw new \RuntimeException('Pago sin referencia de pedido.');
        }

        $pedido = $this->checkoutService->obtenerPedido($pedidoId);
        if (!$pedido) {
            throw new \RuntimeException('Pedido no encontrado.');
        }

        // Ya procesado: no repetir (webhook + retorno pueden llegar ambos).
        if ($pedido['estado'] !== 'pendiente') {
            return ['pedido_id' => $pedidoId, 'estado' => $pedido['estado'], 'ya_procesado' => true];
        }

        $userId = (int)($pedido['id_usuario'] ?? 0);
        $transaccionId = 'MP-' . $paymentId;

        $this->repository->registrarPago(
            pedidoId: $pedidoId,
            metodoPago: 'mercadopago',
            monto: (int)$pedido['total'],
            referenciaExterna: $transaccionId,
            estado: $estado === 'approved' ? 'aprobado' : 'rechazado',
            respuesta: json_encode($pago)
        );

        if ($estado === 'approved') {
            $this->aprobarPedido($pedidoId, $pedido, $userId, $transaccionId);
            return ['pedido_id' => $pedidoId, 'estado' => 'pagado'];
        }

        if (in_array($estado, ['rejected', 'cancelled'], true)) {
            $this->checkoutService->actualizarEstado($pedidoId, 'cancelado', $userId, 'Pago rechazado en MercadoPago.');
            return ['pedido_id' => $pedidoId, 'estado' => 'rechazado'];
        }

        return ['pedido_id' => $pedidoId, 'estado' => 'pendiente'];   // in_process / pending
    }

    /**
     * Aplica los efectos de un pago aprobado: descuenta stock, marca pagado, registra
     * movimientos de inventario y vacía el carrito. Atómico (todo o nada).
     */
    private function aprobarPedido(int $pedidoId, array $pedido, int $userId, string $transaccionId): void
    {
        $db = Database::getInstance();
        try {
            $db->beginTransaction();

            $this->checkoutService->descontarStock($pedidoId);
            $this->checkoutService->actualizarEstado(
                pedidoId: $pedidoId,
                nuevoEstado: 'pagado',
                userId: $userId ?: null,
                comentario: 'Pago aprobado. Transacción: ' . $transaccionId
            );

            $inventarioService = new InventarioService(new InventarioRepository());
            foreach ($pedido['detalle'] ?? [] as $detalle) {
                $inventarioService->registrarEgreso(
                    productoId: (int)$detalle['id_producto'],
                    cantidad: (int)$detalle['cantidad'],
                    pedidoId: $pedidoId,
                    motivo: 'Venta - Pedido #' . $pedidoId
                );
            }

            // Recién con el pago aprobado se vacía el carrito: un rechazo lo deja intacto.
            if ($userId) {
                $carritoRepo = new CarritoRepository();
                $carrito = $carritoRepo->obtenerCarritoActivoPorUsuario($userId);
                if ($carrito) {
                    $carritoRepo->desactivarCarrito((int)$carrito['id']);
                }
            }

            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /** Pago simulado (sin credenciales MP): aprueba al instante. Solo dev. */
    private function procesarSimulado(int $pedidoId, int $userId, int $monto): array
    {
        $transaccionId = 'SIM-' . strtoupper(bin2hex(random_bytes(6)));
        $pedido = $this->checkoutService->obtenerPedido($pedidoId);

        $this->repository->registrarPago(
            pedidoId: $pedidoId,
            metodoPago: 'simulado',
            monto: $monto,
            referenciaExterna: $transaccionId,
            estado: 'aprobado',
            respuesta: json_encode(['estado' => 'aprobado', 'simulado' => true])
        );
        $this->aprobarPedido($pedidoId, $pedido, $userId, $transaccionId);

        return [
            'modo'           => 'simulado',
            'estado'         => 'aprobado',
            'pedido_id'      => $pedidoId,
            'transaccion_id' => $transaccionId,
            'monto'          => $monto,
        ];
    }

    /**
     * Consulta el estado del pago de un pedido (lo usa el front para hacer polling).
     */
    public function consultarEstadoPago(int $pedidoId): ?array
    {
        return $this->repository->obtenerPagoPorPedido($pedidoId);
    }

    /**
     * Webhook de MercadoPago. MP avisa con type=payment & data.id; confirmamos contra su API.
     */
    public function procesarWebhook(string $tipo, string $dataId): void
    {
        if ($tipo !== 'payment' || $dataId === '') {
            return; // ignoramos otros eventos (merchant_order, etc.)
        }
        $this->confirmarPagoMercadoPago($dataId);
    }
}
