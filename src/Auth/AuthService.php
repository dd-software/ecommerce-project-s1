<?php

declare(strict_types=1);

/**
 * AuthService - Lógica de negocio de autenticación
 */
namespace App\Auth;

class AuthService
{
    private AuthRepository $repository;

    public function __construct(AuthRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Registra un nuevo usuario
     * @throws \RuntimeException si el email ya existe
     */
    public function registrar(array $data): array
    {
        // Verificar email único
        if ($this->repository->existeEmail($data['email'])) {
            throw new \RuntimeException('El email ya está registrado.');
        }

        // Hashear contraseña con bcrypt
        $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        $usuarioData = [
            'nombre'        => trim($data['nombre']),
            'apellido'      => trim($data['apellido']),
            'email'         => strtolower(trim($data['email'])),
            'password_hash' => $hash,
            'rol'           => 'cliente',
            'activo'        => 1,
        ];

        $id = $this->repository->crear($usuarioData);

        $usuarioData['id'] = $id;
        return $usuarioData;
    }

    /**
     * Autentica un usuario con email y contraseña
     * @throws \RuntimeException si las credenciales son inválidas
     */
    public function autenticar(string $email, string $password): array
    {
        $usuario = $this->repository->buscarPorEmail($email);

        if (!$usuario) {
            throw new \RuntimeException('Credenciales incorrectas o cuenta suspendida.');
        }

        // Verificar si la cuenta está activa (RN-004)
        if (!$usuario['activo']) {
            throw new \RuntimeException('Credenciales incorrectas o cuenta suspendida.');
        }

        // Verificar bloqueo por intentos fallidos
        if ($usuario['bloqueado_hasta'] && strtotime($usuario['bloqueado_hasta']) > time()) {
            throw new \RuntimeException('Cuenta temporalmente bloqueada. Intente nuevamente en 15 minutos.');
        }

        // Verificar contraseña
        if (!password_verify($password, $usuario['password_hash'])) {
            // Incrementar intentos fallidos
            $this->repository->incrementarIntentos($usuario['id']);
            throw new \RuntimeException('Credenciales incorrectas o cuenta suspendida.');
        }

        // Resetear intentos fallidos y actualizar último login
        $this->repository->resetearIntentos($usuario['id']);
        $this->repository->actualizarUltimoLogin($usuario['id']);

        return $usuario;
    }

    /**
     * Obtiene el perfil de un usuario
     */
    public function obtenerPerfil(int $userId): ?array
    {
        return $this->repository->buscarPorId($userId);
    }

    /**
     * Actualiza el perfil de un usuario
     */
    public function actualizarPerfil(int $userId, array $data): ?array
    {
        $camposPermitidos = ['nombre', 'apellido', 'telefono'];
        $actualizacion = [];

        foreach ($camposPermitidos as $campo) {
            if (isset($data[$campo])) {
                $actualizacion[$campo] = trim($data[$campo]);
            }
        }

        if (!empty($actualizacion)) {
            $this->repository->actualizar($userId, $actualizacion);
        }

        return $this->repository->buscarPorId($userId);
    }
}
