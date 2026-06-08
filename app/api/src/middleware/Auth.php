<?php
class Auth {
    public static function requireAuth(PDO $db): array {
        $token = self::extractToken();
        $payload = JWT::decode($token);

        if (!$payload) {
            Response::error('TOKEN_INVALIDO', 'Token inválido o expirado.', 401);
        }

        $stmt = $db->prepare('SELECT id, nombre, email, rol, habilitado FROM usuarios WHERE id = ?');
        $stmt->execute([$payload['sub']]);
        $user = $stmt->fetch();

        if (!$user) {
            Response::error('USUARIO_NO_ENCONTRADO', 'El usuario no existe.', 401);
        }

        if (!$user['habilitado']) {
            // RN-004: usuarios deshabilitados no pueden acceder
            Response::error('USUARIO_DESHABILITADO', 'Tu cuenta está deshabilitada. Contacta al administrador.', 403);
        }

        return $user;
    }

    public static function requireAdmin(PDO $db): array {
        $user = self::requireAuth($db);
        // RN-002: solo administradores acceden al dashboard
        if ($user['rol'] !== 'administrador') {
            Response::error('ACCESO_DENEGADO', 'Se requieren permisos de administrador.', 403);
        }
        return $user;
    }

    private static function extractToken(): string {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $auth = $headers['Authorization']
            ?? $headers['authorization']
            ?? $_SERVER['HTTP_AUTHORIZATION']
            ?? '';

        if (!str_starts_with($auth, 'Bearer ')) {
            Response::error('NO_AUTENTICADO', 'Se requiere autenticación.', 401);
        }

        return substr($auth, 7);
    }
}
