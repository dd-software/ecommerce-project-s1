<?php

declare(strict_types=1);

namespace App\Contacto;

use App\Core\{Request, Response};
use App\Integracion\SmtpMailer;

class ContactoController
{
    public function enviar(Request $request, Response $response, array $params): void
    {
        $body    = $request->getBody();
        $nombre  = trim($body['nombre']  ?? '');
        $email   = trim($body['email']   ?? '');
        $asunto  = trim($body['asunto']  ?? '');
        $mensaje = trim($body['mensaje'] ?? '');

        if (!$nombre || !$email || !$asunto || !$mensaje) {
            $response->error('VALIDATION_ERROR', 'Todos los campos son requeridos.', 422);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response->error('VALIDATION_ERROR', 'Email inválido.', 422);
            return;
        }

        // Email que llega a la bandeja de QuadCore
        $asuntoAdmin = "[QuadCore Contacto] {$asunto} — de {$nombre}";
        $cuerpoAdmin =
            "Nuevo mensaje de Atención al Cliente:\n\n"
            . "Nombre:  {$nombre}\n"
            . "Email:   {$email}\n"
            . "Asunto:  {$asunto}\n\n"
            . "Mensaje:\n{$mensaje}\n\n"
            . "---\n"
            . "Enviado desde el formulario de contacto de QuadCore.";

        // Respuesta automática que recibe el cliente
        $asuntoUsuario = "Hemos recibido tu mensaje — QuadCore";
        $cuerpoUsuario =
            "Hola {$nombre},\n\n"
            . "Gracias por contactarte con el servicio al cliente de QuadCore.\n\n"
            . "Hemos recibido tu mensaje y te responderemos dentro de 24 horas hábiles.\n\n"
            . "Para tu referencia, este fue el detalle de tu consulta:\n"
            . "  Asunto:  {$asunto}\n"
            . "  Mensaje: {$mensaje}\n\n"
            . "Si tienes alguna urgencia, también puedes comunicarte con nosotros al\n"
            . "+56 2 2123 4567 (Lun–Vie 9:00–18:00 · Sáb 10–14h).\n\n"
            . "Saludos,\n"
            . "Equipo de Atención al Cliente\n"
            . "QuadCore";

        try {
            if ($this->smtpConfigurado()) {
                $mailer = new SmtpMailer(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_FROM);
                $mailer->send(CONTACTO_EMAIL, $asuntoAdmin, $cuerpoAdmin);
                $mailer->send($email, $asuntoUsuario, $cuerpoUsuario);
            } else {
                $this->registrarLog(CONTACTO_EMAIL, $asuntoAdmin, $cuerpoAdmin);
                $this->registrarLog($email, $asuntoUsuario, $cuerpoUsuario);
            }

            $response->json(['mensaje' => 'Mensaje enviado correctamente.']);
        } catch (\Exception $e) {
            $response->error('EMAIL_ERROR', 'No se pudo enviar el mensaje. Intenta más tarde.', 500);
        }
    }

    private function smtpConfigurado(): bool
    {
        return SMTP_HOST !== '' && SMTP_HOST !== 'smtp.example.com'
            && SMTP_USER !== '' && SMTP_USER !== 'noreply@example.com'
            && SMTP_PASS !== '' && SMTP_PASS !== 'smtp_password_here'
            && SMTP_PASS !== 'aqui_tu_contrasena_de_aplicacion_gmail';
    }

    private function registrarLog(string $to, string $subject, string $body): void
    {
        $logDir = dirname(__DIR__, 2) . '/var/log';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $entry = sprintf(
            "[%s] TO: %s | SUBJECT: %s\nBODY:\n%s\n%s\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            $body,
            str_repeat('-', 60)
        );
        @file_put_contents($logDir . '/mail.log', $entry, FILE_APPEND);
    }
}
