<?php

declare(strict_types=1);

/**
 * Clase Response - Encapsula la respuesta HTTP
 * Proporciona formato consistente de respuestas JSON
 */
namespace App\Core;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private $body = null;

    /**
     * Establece el código de estado HTTP
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Establece un header HTTP
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Envía una respuesta JSON exitosa
     */
    public function json($data, int $statusCode = 200, array $meta = []): void
    {
        $this->statusCode = $statusCode;
        $this->setHeader('Content-Type', 'application/json; charset=utf-8');

        $response = [
            'success' => true,
            'data'    => $data,
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        $this->body = $response;
        $this->send();
    }

    /**
     * Envía una respuesta JSON con datos paginados
     */
    public function paginated(array $data, int $total, int $page, int $perPage): void
    {
        $this->json($data, 200, [
            'pagination' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => (int)ceil($total / $perPage),
            ]
        ]);
    }

    /**
     * Envía una respuesta de error estructurada
     */
    public function error(string $code, string $message, int $statusCode = 400, ?string $field = null): void
    {
        $this->statusCode = $statusCode;
        $this->setHeader('Content-Type', 'application/json; charset=utf-8');

        $response = [
            'success' => false,
            'error'   => [
                'code'    => $code,
                'message' => $message,
            ],
        ];

        if ($field !== null) {
            $response['error']['field'] = $field;
        }

        $this->body = $response;
        $this->send();
    }

    /**
     * Envía la respuesta al cliente.
     * Limpia TODOS los niveles de output buffering para garantizar que
     * ningún warning/notice de PHP corrompa la respuesta JSON.
     */
    private function send(): void
    {
        // Limpiar TODOS los niveles de output buffer para garantizar JSON limpio
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Establecer código de estado
        http_response_code($this->statusCode);

        // Enviar headers
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        // Enviar body si existe
        if ($this->body !== null) {
            echo json_encode($this->body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        exit;
    }

    /**
     * Redirigir a otra URL
     */
    public function redirect(string $url, int $statusCode = 302): void
    {
        // Limpiar buffers antes de redirigir
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }

    /**
     * Envía una respuesta HTML (para páginas renderizadas)
     */
    public function html(string $html, int $statusCode = 200): void
    {
        // Limpiar buffers de errores PHP antes de enviar HTML
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($statusCode);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }
}
