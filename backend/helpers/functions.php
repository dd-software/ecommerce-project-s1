<?php

declare(strict_types=1);

/**
 * Funciones helper para el panel de administración
 */

/**
 * Formatea un precio en centavos a string legible (CLP)
 */
function formatPrice(int $cents): string
{
    return '$' . number_format($cents / 100, 0, ',', '.');
}

/**
 * Sanitiza una cadena para salida HTML (previene XSS)
 */
function e(mixed $value): string
{
    if ($value === null) return '';
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Genera un token CSRF y lo almacena en sesión
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Genera un campo input hidden con el token CSRF
 */
function csrfField(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . csrfToken() . '">';
}

/**
 * Valida un token CSRF recibido en POST
 */
function validateCsrf(): bool
{
    $token = $_POST['_csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (empty($token) || empty($sessionToken)) {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

/**
 * Calcula la URL base del proyecto (ruta HTTP)
 */
function getBasePath(): string
{
    return defined('BASE_URL') ? BASE_URL : '';
}

/**
 * Calcula la URL base del backend (ruta HTTP)
 */
function getBackendPath(): string
{
    return getBasePath() . '/backend';
}

/**
 * Calcula la URL de la API (ruta HTTP)
 */
function getApiPath(): string
{
    return getBasePath() . '/api';
}

/**
 * Genera la URL completa para un asset del backend
 */
function adminAsset(string $path): string
{
    return getBackendPath() . '/assets/' . ltrim($path, '/');
}

/**
 * Genera un badge de estado con colores Bootstrap
 */
function estadoBadge(string $estado): string
{
    $colores = [
        'pendiente'       => 'warning',
        'pagado'          => 'success',
        'en_preparacion'  => 'info',
        'enviado'         => 'primary',
        'entregado'       => 'success',
        'cancelado'       => 'danger',
        'aprobado'        => 'success',
        'rechazado'       => 'danger',
        'reembolsado'     => 'secondary',
    ];

    $color = $colores[$estado] ?? 'secondary';
    $label = ucfirst(str_replace('_', ' ', $estado));

    return '<span class="badge bg-' . $color . ' bg-opacity-10 text-' . $color . ' fw-medium">' . e($label) . '</span>';
}

/**
 * Devuelve la clase CSS para marcar el item activo en el sidebar
 */
function isActivePage(string $page): string
{
    $currentFile = basename($_SERVER['SCRIPT_FILENAME'] ?? '', '.php');
    return ($currentFile === $page) ? 'active' : '';
}

/**
 * Formatea una fecha MySQL a formato legible
 */
function formatDate(string $date, string $format = 'd/m/Y H:i'): string
{
    if (empty($date)) return '-';
    $dt = new DateTime($date);
    return $dt->format($format);
}
