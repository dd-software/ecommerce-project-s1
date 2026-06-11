<?php

declare(strict_types=1);

/**
 * Clase Request - Encapsula la petición HTTP
 * Proporciona acceso seguro a parámetros de entrada
 */
namespace App\Core;

class Request
{
    private array $queryParams;
    private array $body;
    private array $headers;
    private array $attributes = [];
    private string $method;
    private string $uri;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $this->queryParams = $_GET;
        $this->headers = $this->parseHeaders();

        // Parsear body según Content-Type
        $contentType = $this->getHeader('Content-Type') ?? '';
        if (str_contains($contentType, 'application/json')) {
            $rawBody = file_get_contents('php://input');
            $this->body = json_decode($rawBody ?: '{}', true) ?: [];
        } else {
            $this->body = $_POST;
        }
    }

    /**
     * Parsea los headers HTTP de la petición
     */
    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace('_', '-', substr($key, 5));
                $headers[$headerName] = $value;
            }
        }
        // Headers especiales
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['CONTENT-TYPE'] = $_SERVER['CONTENT_TYPE'];
        }
        return $headers;
    }

    /**
     * Obtiene el método HTTP
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Obtiene la URI de la petición
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Obtiene un parámetro de query string sanitizado
     */
    public function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    /**
     * Obtiene todos los parámetros de query string
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * Obtiene un campo del body de la petición
     */
    public function getBody(string $key = '', mixed $default = null): mixed
    {
        if ($key === '') {
            return $this->body;
        }
        return $this->body[$key] ?? $default;
    }

    /**
     * Obtiene un header HTTP
     */
    public function getHeader(string $name): ?string
    {
        $name = strtoupper(str_replace('-', '-', $name));
        return $this->headers[$name] ?? null;
    }

    /**
     * Obtiene el token JWT del header Authorization
     */
    public function getBearerToken(): ?string
    {
        $auth = $this->getHeader('Authorization');
        if ($auth && str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }
        return null;
    }

    /**
     * Establece un atributo en la request (útil para middleware)
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Obtiene un atributo de la request
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Obtiene la IP del cliente
     */
    public function getClientIp(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_X_REAL_IP']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '127.0.0.1';
    }

    /**
     * Obtiene el user agent
     */
    public function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Valida que los campos requeridos estén presentes en el body
     * @throws \InvalidArgumentException
     */
    public function validateRequired(array $campos): void
    {
        $faltantes = [];
        foreach ($campos as $campo) {
            if (!isset($this->body[$campo]) || (is_string($this->body[$campo]) && trim($this->body[$campo]) === '')) {
                $faltantes[] = $campo;
            }
        }
        if (!empty($faltantes)) {
            throw new \InvalidArgumentException(
                'Campos requeridos faltantes: ' . implode(', ', $faltantes)
            );
        }
    }
}
