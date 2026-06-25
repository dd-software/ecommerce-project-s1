<?php
declare(strict_types=1);

namespace Src\Core;

/**
 * Class Router
 * Enrutador base de PHP puro para manejar las peticiones HTTP de la API REST.
 */
class Router {
    /**
     * @var array Lista de rutas registradas.
     */
    private array $routes = [];

    /**
     * Registra una nueva ruta en el sistema.
     *
     * @param string $method Método HTTP (GET, POST, PUT, DELETE).
     * @param string $path La ruta URI (e.g. /api/auth/login).
     * @param callable|array $handler El controlador y método a ejecutar.
     */
    public function add(string $method, string $path, callable|array $handler): void {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler
        ];
    }

    /**
     * Despacha la petición actual buscando coincidencias en las rutas.
     *
     * @param string $method Método HTTP de la petición actual.
     * @param string $uri La URI solicitada.
     */
    public function dispatch(string $method, string $uri): void {
        $uri = parse_url($uri, PHP_URL_PATH) ?: '/';
        
        // Adaptación automática para despliegues locales (XAMPP/WAMP) en subdirectorios
        if (($pos = strpos($uri, '/api/')) !== false) {
            $uri = substr($uri, $pos);
        }
        
        foreach ($this->routes as $route) {
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_-]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';
            
            if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                call_user_func_array($route['handler'], [$params]);
                return;
            }
        }

        Response::error('Ruta no encontrada (Not Found)', 404);
    }
}
