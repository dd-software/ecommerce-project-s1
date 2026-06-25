<?php
declare(strict_types=1);

namespace Src\Core;

/**
 * Class Response
 * Manejador centralizado para estandarizar las respuestas JSON de la API REST.
 */
class Response {
    /**
     * Retorna una respuesta JSON estándar con el código HTTP correspondiente.
     *
     * @param array $data Los datos a retornar.
     * @param int $statusCode El código de estado HTTP (por defecto 200).
     */
    public static function json(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    /**
     * Retorna un error estandarizado en formato JSON.
     *
     * @param string $message El mensaje de error.
     * @param int $statusCode El código de estado HTTP (por defecto 400).
     */
    public static function error(string $message, int $statusCode = 400): void {
        self::json(['error' => $message], $statusCode);
    }
}
