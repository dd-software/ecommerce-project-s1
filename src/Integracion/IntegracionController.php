<?php

declare(strict_types=1);

/**
 * IntegracionController - Notificaciones, exportación y health check
 */
namespace App\Integracion;

use App\Core\{Request, Response};

class IntegracionController
{
    private IntegracionService $service;

    public function __construct()
    {
        $this->service = new IntegracionService(new IntegracionRepository());
    }

    /**
     * GET /api/health
     * Health check del sistema
     */
    public function health(Request $request, Response $response, array $params): void
    {
        $status = $this->service->healthCheck();
        $response->json($status);
    }

    /**
     * POST /api/notificaciones/email
     * Envía una notificación por email (simulado)
     */
    public function enviarEmail(Request $request, Response $response, array $params): void
    {
        try {
            $data = $request->getBody();
            $request->validateRequired(['destinatario', 'asunto', 'cuerpo']);

            $resultado = $this->service->encolarEmail(
                destinatario: $data['destinatario'],
                asunto: $data['asunto'],
                cuerpo: $data['cuerpo'],
                tipo: $data['tipo'] ?? 'email'
            );

            $response->json($resultado, 201);

        } catch (\InvalidArgumentException $e) {
            $response->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al enviar notificación.', 500);
        }
    }

    /**
     * GET /api/notificaciones/cola
     * Lista notificaciones pendientes (admin)
     */
    public function listarCola(Request $request, Response $response, array $params): void
    {
        $user = $request->getAttribute('authenticated_user');
        if (!$user || $user['rol'] !== 'admin') {
            $response->error('INSUFFICIENT_PERMISSIONS', 'Acceso denegado.', 403);
            return;
        }

        try {
            $notificaciones = $this->service->listarCola();
            $response->json($notificaciones);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al listar notificaciones.', 500);
        }
    }

    /**
     * GET /api/exportar/pedidos
     * Exporta pedidos en JSON
     */
    public function exportarPedidos(Request $request, Response $response, array $params): void
    {
        $user = $request->getAttribute('authenticated_user');
        if (!$user || !in_array($user['rol'], ['admin', 'supervisor'])) {
            $response->error('INSUFFICIENT_PERMISSIONS', 'Acceso denegado.', 403);
            return;
        }

        try {
            $fechaDesde = $request->getQuery('desde');
            $fechaHasta = $request->getQuery('hasta');

            $datos = $this->service->exportarPedidos($fechaDesde, $fechaHasta);

            // Respuesta como descarga JSON
            $response->setHeader('Content-Type', 'application/json; charset=utf-8');
            $response->setHeader('Content-Disposition', 'attachment; filename="pedidos_' . date('Y-m-d') . '.json"');
            $response->json($datos);

        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al exportar pedidos.', 500);
        }
    }

    /**
     * GET /api/exportar/productos
     * Exporta productos en JSON
     */
    public function exportarProductos(Request $request, Response $response, array $params): void
    {
        $user = $request->getAttribute('authenticated_user');
        if (!$user || !in_array($user['rol'], ['admin', 'supervisor', 'vendedor'])) {
            $response->error('INSUFFICIENT_PERMISSIONS', 'Acceso denegado.', 403);
            return;
        }

        try {
            $datos = $this->service->exportarProductos();
            $response->setHeader('Content-Disposition', 'attachment; filename="productos_' . date('Y-m-d') . '.json"');
            $response->json($datos);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al exportar productos.', 500);
        }
    }

    /**
     * GET /api/exportar/reporte-ventas
     * Exporta reporte de ventas en JSON
     */
    public function exportarReporteVentas(Request $request, Response $response, array $params): void
    {
        $user = $request->getAttribute('authenticated_user');
        if (!$user || !in_array($user['rol'], ['admin', 'supervisor'])) {
            $response->error('INSUFFICIENT_PERMISSIONS', 'Acceso denegado.', 403);
            return;
        }

        try {
            $datos = $this->service->exportarReporteVentas();
            $response->setHeader('Content-Disposition', 'attachment; filename="reporte_ventas_' . date('Y-m-d') . '.json"');
            $response->json($datos);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al exportar reporte.', 500);
        }
    }

    /**
     * POST /api/notificaciones/confirmacion-pedido
     * Envía notificación de confirmación de pedido al cliente
     */
    public function notificarConfirmacion(Request $request, Response $response, array $params): void
    {
        try {
            $data = $request->getBody();
            $request->validateRequired(['pedido_id', 'email_cliente']);

            $this->service->notificarConfirmacionPedido(
                pedidoId: (int)$data['pedido_id'],
                emailCliente: $data['email_cliente'],
                nombreCliente: $data['nombre_cliente'] ?? 'Cliente'
            );

            $response->json(['mensaje' => 'Notificación encolada exitosamente.']);

        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al encolar notificación.', 500);
        }
    }
}
