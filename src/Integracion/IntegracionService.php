<?php

declare(strict_types=1);

/**
 * IntegracionService - Servicios de integración: notificaciones, exportación, health
 */
namespace App\Integracion;

use App\Core\Database;

class IntegracionService
{
    private IntegracionRepository $repository;

    public function __construct(IntegracionRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Health check del sistema
     */
    public function healthCheck(): array
    {
        $dbStatus = 'error';
        try {
            $db = Database::getInstance()->getConnection();
            $db->query("SELECT 1");
            $dbStatus = 'ok';
        } catch (\Exception $e) {
            $dbStatus = 'error: ' . $e->getMessage();
        }

        return [
            'status'    => $dbStatus === 'ok' ? 'healthy' : 'degraded',
            'timestamp' => date('c'),
            'version'   => '1.0.0',
            'services'  => [
                'database'   => $dbStatus,
                'php'        => PHP_VERSION,
                'cache'      => 'ok',
            ],
            'uptime'    => time(),
        ];
    }

    /**
     * Encola un email para envío asíncrono (simulado)
     * RN-H01: No bloquea el hilo principal
     */
    public function encolarEmail(string $destinatario, string $asunto, string $cuerpo, string $tipo = 'email'): array
    {
        // Validar email
        if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email de destinatario inválido.');
        }

        $id = $this->repository->encolarNotificacion(
            destinatario: $destinatario,
            asunto: $asunto,
            cuerpo: $cuerpo,
            tipo: $tipo
        );

        // Simular envío inmediato (en producción sería async mediante cola)
        $this->simularEnvioEmail($destinatario, $asunto, $cuerpo);
        $this->repository->marcarEnviada($id);

        return [
            'id'           => $id,
            'destinatario' => $destinatario,
            'estado'       => 'enviado',
            'mensaje'      => 'Notificación encolada y enviada exitosamente.',
        ];
    }

    /**
     * Simula el envío de un email (en producción conectaría con SMTP)
     * RN-H02: Patrón Adapter para aislar la implementación concreta
     */
    private function simularEnvioEmail(string $destinatario, string $asunto, string $cuerpo): void
    {
        // En producción, esto usaría mail() o PHPMailer/SwiftMailer
        // Por ahora, simulamos escritura en log
        $logDir = dirname(__DIR__, 2) . '/var/log';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/mail.log';
        $logEntry = sprintf(
            "[%s] TO: %s | SUBJECT: %s | BODY: %s\n",
            date('Y-m-d H:i:s'),
            $destinatario,
            $asunto,
            substr($cuerpo, 0, 100) . '...'
        );

        @file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    /**
     * Lista notificaciones pendientes
     */
    public function listarCola(): array
    {
        return $this->repository->listarCola();
    }

    /**
     * Exporta pedidos en formato JSON estructurado
     */
    public function exportarPedidos(?string $fechaDesde, ?string $fechaHasta): array
    {
        $pedidos = $this->repository->obtenerPedidosParaExportar($fechaDesde, $fechaHasta);

        return [
            'exportacion' => [
                'tipo'        => 'pedidos',
                'fecha'       => date('c'),
                'total'       => count($pedidos),
                'filtros'     => [
                    'desde' => $fechaDesde,
                    'hasta' => $fechaHasta,
                ],
            ],
            'pedidos' => $pedidos,
        ];
    }

    /**
     * Exporta catálogo de productos en JSON
     */
    public function exportarProductos(): array
    {
        $productos = $this->repository->obtenerProductosParaExportar();

        return [
            'exportacion' => [
                'tipo'  => 'productos',
                'fecha' => date('c'),
                'total' => count($productos),
            ],
            'productos' => $productos,
        ];
    }

    /**
     * Exporta reporte de ventas en JSON
     */
    public function exportarReporteVentas(): array
    {
        $ventas = $this->repository->obtenerReporteVentasExportar();

        return [
            'exportacion' => [
                'tipo'  => 'reporte_ventas',
                'fecha' => date('c'),
                'total_registros' => count($ventas),
            ],
            'ventas' => $ventas,
        ];
    }

    /**
     * Encola notificación de confirmación de pedido
     */
    public function notificarConfirmacionPedido(int $pedidoId, string $emailCliente, string $nombreCliente): void
    {
        $asunto = "Confirmación de Pedido #{$pedidoId}";
        $cuerpo = "Hola {$nombreCliente},\n\n"
                . "Tu pedido #{$pedidoId} ha sido confirmado y está siendo procesado.\n\n"
                . "Puedes revisar el estado de tu pedido en tu cuenta.\n\n"
                . "Gracias por tu compra.\n"
                . "Equipo UCT Ecommerce";

        $this->encolarEmail($emailCliente, $asunto, $cuerpo, 'confirmacion_pedido');
    }
}
