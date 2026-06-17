<?php

declare(strict_types=1);

/**
 * Configuración de la aplicación
 * Carga variables de entorno y define constantes globales
 */

// Cargar .env manualmente (sin dependencia vlucas/phpdotenv para simplicidad)
function cargarEnv(string $ruta): void
{
    if (!file_exists($ruta)) {
        return;
    }

    $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        // Ignorar comentarios
        if (str_starts_with(trim($linea), '#')) {
            continue;
        }

        if (str_contains($linea, '=')) {
            [$clave, $valor] = explode('=', $linea, 2);
            $clave = trim($clave);
            $valor = trim($valor);

            // Quitar comillas si las tiene
            $valor = trim($valor, '"\'');
            $valor = trim($valor);

            if (!array_key_exists($clave, $_ENV) && getenv($clave) === false) {
                $_ENV[$clave] = $valor;
                putenv("{$clave}={$valor}");
            }
        }
    }
}

// Cargar .env desde la raíz del proyecto
cargarEnv(dirname(__DIR__) . '/.env');

// Configuración general
define('APP_NAME', 'UCT Ecommerce');
define('APP_URL', 'https://teclab.uct.cl/~mvaldebenito2025/public');
define('APP_ENV', 'development'); // development, production, testing
define('APP_DEBUG', true);

// Configuración de rutas
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// Constantes de la aplicación
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'clave_secreta_por_defecto_cambiar_en_produccion');
define('JWT_EXPIRY', (int)($_ENV['JWT_EXPIRY'] ?? 7200));
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.example.com');
define('SMTP_PORT', (int)($_ENV['SMTP_PORT'] ?? 587));
define('SMTP_USER', $_ENV['SMTP_USER'] ?? '');
define('SMTP_PASS', $_ENV['SMTP_PASS'] ?? '');
define('SMTP_FROM', $_ENV['SMTP_FROM'] ?? 'noreply@example.com');

// Configuración de error según entorno
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

// Zona horaria
date_default_timezone_set('America/Santiago');
