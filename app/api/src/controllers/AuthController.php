<?php
class AuthController {
    public function __construct(private PDO $db) {}

    public function handle(string $method, array $segments): void {
        $action = $segments[0] ?? '';

        match (true) {
            $method === 'POST' && $action === 'login'    => $this->login(),
            $method === 'POST' && $action === 'registro' => $this->registro(),
            $method === 'GET'  && $action === 'me'       => $this->me(),
            default => Response::error('RUTA_NO_ENCONTRADA', 'Ruta de auth no válida.', 404),
        };
    }

    private function login(): void {
        $body = $this->body();
        $email    = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if (!$email || !$password) {
            Response::error('DATOS_INCOMPLETOS', 'Email y contraseña son obligatorios.');
        }

        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE email = ?');
        $stmt->execute([strtolower($email)]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Response::error('CREDENCIALES_INVALIDAS', 'Email o contraseña incorrectos.', 401);
        }

        if (!$user['habilitado']) {
            // RN-004
            Response::error('USUARIO_DESHABILITADO', 'Tu cuenta está deshabilitada.', 403);
        }

        $token = JWT::encode(['sub' => $user['id'], 'rol' => $user['rol']]);

        Response::success([
            'token'   => $token,
            'usuario' => $this->safeUser($user),
        ]);
    }

    private function registro(): void {
        $body  = $this->body();
        $nombre   = trim($body['nombre']   ?? '');
        $email    = strtolower(trim($body['email'] ?? ''));
        $password = $body['password'] ?? '';

        if (!$nombre || !$email || !$password) {
            Response::error('DATOS_INCOMPLETOS', 'Nombre, email y contraseña son obligatorios.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('EMAIL_INVALIDO', 'El formato del email no es válido.');
        }
        if (strlen($password) < 6) {
            Response::error('PASSWORD_CORTA', 'La contraseña debe tener al menos 6 caracteres.');
        }

        $check = $this->db->prepare('SELECT id FROM usuarios WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            Response::error('EMAIL_DUPLICADO', 'Ya existe una cuenta con ese email.', 409);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$nombre, $email, password_hash($password, PASSWORD_BCRYPT), 'cliente']);
        $userId = (int)$this->db->lastInsertId();

        $user  = $this->db->query("SELECT * FROM usuarios WHERE id = $userId")->fetch();
        $token = JWT::encode(['sub' => $user['id'], 'rol' => $user['rol']]);

        Response::success(['token' => $token, 'usuario' => $this->safeUser($user)], 201);
    }

    private function me(): void {
        $user = Auth::requireAuth($this->db);
        Response::success($user);
    }

    private function safeUser(array $user): array {
        unset($user['password_hash']);
        return $user;
    }

    private function body(): array {
        return (array)json_decode(file_get_contents('php://input'), true);
    }
}
