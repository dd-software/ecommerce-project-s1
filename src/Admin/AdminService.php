<?php

declare(strict_types=1);

/**
 * AdminService - Lógica de negocio del panel de administración
 */
namespace App\Admin;

use App\Checkout\CheckoutRepository;
use App\Catalogo\CatalogoRepository;

class AdminService
{
    private AdminRepository $repository;

    public function __construct(AdminRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Obtiene métricas del dashboard
     */
    public function obtenerDashboard(): array
    {
        return [
            'total_productos'     => $this->repository->contarProductos(),
            'total_pedidos'       => $this->repository->contarPedidos(),
            'total_usuarios'      => $this->repository->contarUsuarios(),
            'total_ventas_hoy'    => $this->repository->ventasHoy(),
            'total_ventas_mes'    => $this->repository->ventasMes(),
            'pedidos_pendientes'  => $this->repository->contarPedidosPorEstado('pendiente'),
            'productos_agotados'  => $this->repository->contarProductosAgotados(),
            'alertas_stock'       => $this->repository->obtenerAlertasStock(),
            'ultimos_pedidos'     => $this->repository->obtenerUltimosPedidos(5),
        ];
    }

    /**
     * Lista productos (admin, incluye inactivos)
     */
    public function listarProductosAdmin(?string $busqueda, int $pagina, int $porPagina): array
    {
        return $this->repository->listarProductosAdmin($busqueda, $pagina, $porPagina);
    }

    /**
     * Crea un nuevo producto
     */
    public function crearProducto(array $data, int $userId): array
    {
        // Generar slug desde el nombre
        $slug = $this->generarSlug($data['nombre']);

        $producto = $this->repository->crearProducto([
            'nombre'          => trim($data['nombre']),
            'slug'            => $slug,
            'descripcion'     => $data['descripcion'] ?? '',
            'precio'          => (int)$data['precio'],
            'stock'           => (int)($data['stock'] ?? 0),
            'id_categoria'    => (int)$data['id_categoria'],
            'imagen_url'      => $data['imagen_url'] ?? null,
            'stock_minimo'    => (int)($data['stock_minimo'] ?? 5),
            'activo'          => 1,
        ]);

        // G-1: registrar en auditoría
        $this->repository->registrarAuditoria('producto', $producto['id'] ?? null, 'CREATE', $userId, 'Creó producto: ' . trim($data['nombre']));

        return $producto;
    }

    /**
     * Actualiza un producto existente
     */
    public function actualizarProducto(int $id, array $data, int $userId): array
    {
        $producto = (new CatalogoRepository())->buscarPorIdAdmin($id);
        if (!$producto) {
            throw new \RuntimeException('Producto no encontrado.');
        }

        $campos = [];
        if (isset($data['nombre'])) {
            $campos['nombre'] = trim($data['nombre']);
            $campos['slug'] = $this->generarSlug($data['nombre'], $id);
        }
        if (isset($data['descripcion'])) $campos['descripcion'] = trim($data['descripcion']);
        if (isset($data['precio'])) $campos['precio'] = (int)$data['precio'];
        if (isset($data['stock'])) $campos['stock'] = (int)$data['stock'];
        if (isset($data['id_categoria'])) $campos['id_categoria'] = (int)$data['id_categoria'];
        if (isset($data['imagen_url'])) $campos['imagen_url'] = $data['imagen_url'];
        if (isset($data['stock_minimo'])) $campos['stock_minimo'] = (int)$data['stock_minimo'];
        if (isset($data['activo'])) $campos['activo'] = (int)$data['activo'];

        $resultado = $this->repository->actualizarProducto($id, $campos);

        // G-1: registrar en auditoría
        $this->repository->registrarAuditoria('producto', $id, 'UPDATE', $userId, 'Actualizó producto #' . $id);

        return $resultado;
    }

    /**
     * Soft-delete de un producto
     */
    public function eliminarProducto(int $id, int $userId): void
    {
        $this->repository->softDeleteProducto($id);

        // G-1: registrar en auditoría
        $this->repository->registrarAuditoria('producto', $id, 'DELETE', $userId, 'Eliminó producto #' . $id);
    }

    /**
     * Lista pedidos para admin
     */
    public function listarPedidosAdmin(?string $estado, int $pagina, int $porPagina): array
    {
        return $this->repository->listarPedidosAdmin($estado, $pagina, $porPagina);
    }

    /**
     * Cambia el estado de un pedido (validando transiciones)
     */
    public function cambiarEstadoPedido(int $pedidoId, string $nuevoEstado, int $userId, string $comentario): void
    {
        $pedido = (new CheckoutRepository())->obtenerPedido($pedidoId);
        if (!$pedido) {
            throw new \RuntimeException('Pedido no encontrado.');
        }

        // Validar transiciones de estado (flujo lógico)
        $transiciones = [
            'pendiente'     => ['pagado', 'cancelado'],
            'pagado'        => ['en_preparacion', 'cancelado'],
            'en_preparacion' => ['enviado', 'cancelado'],
            'enviado'       => ['entregado'],
            'entregado'     => [],
            'cancelado'     => [],
        ];

        $estadoActual = $pedido['estado'];
        if (!isset($transiciones[$estadoActual]) || !in_array($nuevoEstado, $transiciones[$estadoActual])) {
            throw new \RuntimeException(
                "No se puede cambiar de '{$estadoActual}' a '{$nuevoEstado}'."
            );
        }

        $this->repository->actualizarEstadoPedido($pedidoId, $nuevoEstado, $userId, $comentario);

        // Avisar al cliente por correo del nuevo estado (no rompe el cambio si falla)
        $this->notificarCambioEstado($pedido, $nuevoEstado, $pedidoId);
    }

    /**
     * Envía un correo al cliente según el nuevo estado del pedido.
     * Salta 'pagado' (ese ya recibe el correo de confirmación de pago).
     */
    private function notificarCambioEstado(array $pedido, string $estado, int $pedidoId): void
    {
        $email = (string)($pedido['cliente_email'] ?? '');
        if ($email === '') {
            return;
        }

        $mensajes = [
            'en_preparacion' => ['Tu pedido está en preparación', 'Estamos preparando tu pedido para despacharlo. Te avisamos en cuanto salga.'],
            'enviado'        => ['Tu pedido va en camino',        'Tu pedido fue despachado y ya está en camino. Pronto lo vas a recibir.'],
            'entregado'      => ['Tu pedido fue entregado',        '¡Tu pedido fue entregado! Esperamos que lo disfrutes. Gracias por comprar en QuadCore.'],
            'cancelado'      => ['Tu pedido fue cancelado',        'Tu pedido fue cancelado. Si crees que es un error o tenés dudas, escribinos y te ayudamos.'],
        ];
        if (!isset($mensajes[$estado])) {
            return;
        }

        [$asunto, $detalle] = $mensajes[$estado];
        $nombre = trim((string)($pedido['cliente_nombre'] ?? '')) ?: 'cliente';
        $cuerpo = "Hola {$nombre},\n\n"
                . "{$detalle}\n\n"
                . "Pedido #{$pedidoId}.\n\n"
                . "Equipo QuadCore";

        try {
            $integracion = new \App\Integracion\IntegracionService(new \App\Integracion\IntegracionRepository());
            $integracion->encolarEmail($email, "{$asunto} · QuadCore", $cuerpo, 'estado_pedido');
        } catch (\Throwable $e) {
            error_log("Correo de estado pedido #{$pedidoId} no enviado: " . $e->getMessage());
        }
    }

    /**
     * Lista usuarios
     */
    public function listarUsuarios(int $pagina, int $porPagina): array
    {
        return $this->repository->listarUsuarios($pagina, $porPagina);
    }

    /**
     * Activa/desactiva un usuario
     */
    public function cambiarEstadoUsuario(int $id, ?int $activo, int $userId): void
    {
        // G-2: no permitir desactivar al último administrador activo
        if ((int)$activo === 0) {
            $usuario = $this->repository->obtenerUsuario($id);
            if ($usuario && $usuario['rol'] === 'admin' && $this->repository->contarAdminsActivos() <= 1) {
                throw new \RuntimeException('No se puede desactivar al último administrador activo.');
            }
        }

        $this->repository->actualizarEstadoUsuario($id, $activo);

        // G-1: registrar en auditoría
        $this->repository->registrarAuditoria('usuario', $id, 'UPDATE', $userId, 'Cambió estado activo=' . (int)$activo . ' del usuario #' . $id);
    }

    /**
     * Reporte de ventas
     */
    public function obtenerReporteVentas(string $periodo): array
    {
        return $this->repository->obtenerReporteVentas($periodo);
    }

    /**
     * Productos más vendidos
     */
    public function obtenerProductosMasVendidos(int $limite = 10): array
    {
        return $this->repository->obtenerProductosMasVendidos($limite);
    }

    /**
     * Genera un slug único desde un nombre
     */
    private function generarSlug(string $nombre, ?int $excluirId = null): string
    {
        // Convertir a minúsculas, reemplazar espacios y caracteres especiales
        $slug = strtolower(trim($nombre));
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');

        // Asegurar unicidad
        $baseSlug = $slug;
        $contador = 1;
        while ($this->repository->existeSlug($slug, $excluirId)) {
            $slug = $baseSlug . '-' . $contador;
            $contador++;
        }

        return $slug;
    }
}
