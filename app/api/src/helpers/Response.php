<?php
class Response {
    public static function json(mixed $data, int $status = 200): never {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function error(string $codigo, string $mensaje, int $status = 400): never {
        self::json(['codigo' => $codigo, 'mensaje' => $mensaje], $status);
    }

    public static function success(mixed $data = null, int $status = 200): never {
        self::json($data ?? ['ok' => true], $status);
    }

    public static function notFound(string $mensaje = 'Recurso no encontrado.'): never {
        self::error('RECURSO_NO_ENCONTRADO', $mensaje, 404);
    }
}
