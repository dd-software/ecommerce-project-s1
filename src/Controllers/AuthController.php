<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Core\Response;
use Src\Core\JWTHandler;
use Src\Models\User;

/**
 * Class AuthController
 * Controlador para manejar la lógica de autenticación y registro (Módulo C).
 */
class AuthController {
    /**
     * @var User Modelo de usuario.
     */
    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    /**
     * Procesa la solicitud de inicio de sesión y retorna un JWT y los datos de sesión.
     * Endpoint: POST /api/auth/login
     */
    public function login(): void {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['email']) || !isset($input['password'])) {
            Response::error('Email y password son obligatorios', 400);
        }

        $user = $this->userModel->findByEmail($input['email']);

        if (!$user) {
            Response::error('Credenciales inválidas', 401);
        }

        // Regla de Negocio: [RN-004] Usuarios deshabilitados no pueden entrar.
        if (!$user['is_active']) {
            Response::error('Usuario deshabilitado. Contacte a soporte.', 401);
        }

        if (!password_verify($input['password'], $user['password_hash'])) {
            Response::error('Credenciales inválidas', 401);
        }

        $payload = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role_name']
        ];

        $token = JWTHandler::generateToken($payload);

        Response::json([
            'message' => 'Login exitoso',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'email' => $user['email'],
                'role' => $user['role_name']
            ]
        ], 200);
    }

    /**
     * Procesa la solicitud de registro de un nuevo cliente.
     * Endpoint: POST /api/auth/register
     */
    public function register(): void {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['first_name'], $input['last_name'], $input['email'], $input['password'])) {
            Response::error('Todos los campos son obligatorios', 400);
        }

        if ($this->userModel->findByEmail($input['email'])) {
            Response::error('El correo electrónico ya está registrado', 400);
        }

        try {
            $this->userModel->create($input);
            Response::json(['message' => 'Usuario registrado exitosamente'], 201);
        } catch (\Exception $e) {
            Response::error('Error interno al registrar usuario', 500);
        }
    }
}
