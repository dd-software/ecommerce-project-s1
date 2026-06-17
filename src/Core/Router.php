<?php

declare(strict_types=1);

/**
 * Clase Router - Sistema de enrutamiento simple
 * Mapea URLs a controladores y métodos
 */
namespace App\Core;

class Router
{
    private array $routes = [];
    private array $protectedRoutes = [];
    private array $adminRoutes = [];

    /**
     * Registra una ruta GET
     */
    public function get(string $path, callable|array $handler, bool $auth = false, bool $admin = false): self
    {
        return $this->addRoute('GET', $path, $handler, $auth, $admin);
    }

    /**
     * Registra una ruta POST
     */
    public function post(string $path, callable|array $handler, bool $auth = false, bool $admin = false): self
    {
        return $this->addRoute('POST', $path, $handler, $auth, $admin);
    }

    /**
     * Registra una ruta PUT
     */
    public function put(string $path, callable|array $handler, bool $auth = false, bool $admin = false): self
    {
        return $this->addRoute('PUT', $path, $handler, $auth, $admin);
    }

    /**
     * Registra una ruta PATCH
     */
    public function patch(string $path, callable|array $handler, bool $auth = false, bool $admin = false): self
    {
        return $this->addRoute('PATCH', $path, $handler, $auth, $admin);
    }

    /**
     * Registra una ruta DELETE
     */
    public function delete(string $path, callable|array $handler, bool $auth = false, bool $admin = false): self
    {
        return $this->addRoute('DELETE', $path, $handler, $auth, $admin);
    }

    /**
     * Agrega una ruta al registro
     */
    private function addRoute(string $method, string $path, callable|array $handler, bool $auth, bool $admin): self
    {
        // Normalizar path
        $path = '/' . trim($path, '/');
        if ($path === '/') {
            $path = '';
        }

        $routePattern = $this->pathToPattern($path);
        $this->routes[] = [
            'method'  => $method,
            'pattern' => $routePattern,
            'path'    => $path,
            'handler' => $handler,
            'auth'    => $auth,
            'admin'   => $admin,
        ];

        return $this;
    }

    /**
     * Convierte una ruta con parámetros a patrón regex
     * Ej: /api/productos/{id} -> /api/productos/([^/]+)
     */
    private function pathToPattern(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    /**
     * Despacha la petición actual al handler correspondiente
     */
    public function dispatch(Request $request, Response $response): void
    {
        $method = $request->getMethod();
        $uri = rtrim($request->getUri(), '/') ?: '';

        // Buscar ruta que coincida
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extraer parámetros de la URL
                $params = array_filter($matches, fn($key) => is_string($key), ARRAY_FILTER_USE_KEY);

                // Verificar autenticación
                if ($route['auth']) {
                    $user = $request->getAttribute('authenticated_user');
                    if (!$user) {
                        $response->error('TOKEN_INVALID', 'Token de autenticación requerido', 401);
                        return;
                    }

                    // Verificar rol admin
                    if ($route['admin'] && $user['rol'] !== 'admin') {
                        $response->error('INSUFFICIENT_PERMISSIONS', 'Acceso denegado. Se requiere rol de administrador.', 403);
                        return;
                    }
                }

                // Ejecutar handler
                $handler = $route['handler'];
                if (is_array($handler)) {
                    [$controller, $action] = $handler;
                    $controllerInstance = new $controller();
                    call_user_func([$controllerInstance, $action], $request, $response, $params);
                } else {
                    call_user_func($handler, $request, $response, $params);
                }
                return;
            }
        }

        // Ruta no encontrada
        $response->error('ROUTE_NOT_FOUND', 'La ruta solicitada no existe: ' . $method . ' ' . $uri, 404);
    }
}
