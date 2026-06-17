<?php

declare(strict_types=1);

/**
 * Autoloader PSR-4 simple para el proyecto
 * No requiere Composer para funcionar
 */

spl_autoload_register(function (string $class): void {
    // Namespace base del proyecto apunte a la carpeta src
    $prefix = 'App\\';
    $baseDir = dirname(dirname(__DIR__)) . '/src/';

    // Verificar si la clase usa el namespace base (compatible con PHP < 8)
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    // Eliminar el prefijo del namespace
    $relativeClass = substr($class, strlen($prefix));

    // Reemplazar namespace separators por directory separators
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    // Cargar el archivo si existe
    if (file_exists($file)) {
        require_once $file;
    }
});
