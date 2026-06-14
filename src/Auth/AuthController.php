<?php

declare(strict_types=1);

/**
 * AuthController - Maneja registro, login y perfil de usuario
 */
namespace App\Auth;

use App\Core\{Request, Response, JWT, Session, CsrfMiddleware};

class AuthController
{
    private AuthService $service;

    public function __construct()
    {
        $this->service = new AuthService(new AuthRepository());
    }

    /**
     * POST /api/auth/registro
     * Registra un nuevo usuario cliente
     */
    public function registro(Request $request, Response $response, array $params): void
    {
        try {
            $data = $request->getBody();
            $request->validateRequired(['nombre', 'apellido', 'email', 'password']);

            // Validar formato email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $response->error('INVALID_EMAIL', 'El formato del email no es válido.', 422, 'email');
                return;
            }

            // Validar fortaleza de contraseña (spec seguridad §3:
            // mínimo 8 caracteres, al menos una mayúscula, un número y un carácter especial)
            $password = $data['password'];
            if (strlen($password) < 8
                || !preg_match('/[A-Z]/', $password)
                || !preg_match('/[0-9]/', $password)
                || !preg_match('/[^A-Za-z0-9]/', $password)) {
                $response->error('WEAK_PASSWORD', 'La contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un carácter especial.', 422, 'password');
                return;
            }

            $usuario = $this->service->registrar($data);

            // Generar token JWT
            $token = JWT::generarToken($usuario);

            $response->json([
                'token'   => $token,
                'usuario' => [
                    'id'     => $usuario['id'],
                    'nombre' => $usuario['nombre'] . ' ' . $usuario['apellido'],
                    'email'  => $usuario['email'],
                    'rol'    => $usuario['rol'],
                ]
            ], 201);

        } catch (\InvalidArgumentException $e) {
            $response->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            $response->error('EMAIL_EXISTS', $e->getMessage(), 409);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error interno del servidor.', 500);
        }
    }

    /**
     * POST /api/auth/login
     * Inicia sesión y retorna token JWT
     */
    public function login(Request $request, Response $response, array $params): void
    {
        try {
            $data = $request->getBody();
            $request->validateRequired(['email', 'password']);

            $usuario = $this->service->autenticar($data['email'], $data['password']);

            // Regenerar sesión para prevenir session fixation
            Session::regenerar();

            // Generar token JWT
            $token = JWT::generarToken($usuario);

            // Guardar en sesión también
            Session::set('user_id', $usuario['id']);
            Session::set('user_role', $usuario['rol']);

            $response->json([
                'token'   => $token,
                'usuario' => [
                    'id'     => $usuario['id'],
                    'nombre' => $usuario['nombre'] . ' ' . $usuario['apellido'],
                    'email'  => $usuario['email'],
                    'rol'    => $usuario['rol'],
                ]
            ]);

        } catch (\InvalidArgumentException $e) {
            $response->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            $response->error('AUTH_FAILED', $e->getMessage(), 401);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error interno del servidor.', 500);
        }
    }

    /**
     * GET /api/auth/perfil
     * Obtiene el perfil del usuario autenticado
     */
    public function perfil(Request $request, Response $response, array $params): void
    {
        $user = $request->getAttribute('authenticated_user');
        if (!$user) {
            $response->error('TOKEN_INVALID', 'Token requerido', 401);
            return;
        }

        try {
            $perfil = $this->service->obtenerPerfil((int)$user['id']);
            if (!$perfil) {
                $response->error('USER_NOT_FOUND', 'Usuario no encontrado.', 404);
                return;
            }
            $response->json($perfil);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al obtener perfil.', 500);
        }
    }

    /**
     * PATCH /api/auth/perfil
     * Actualiza datos del perfil
     */
    public function actualizarPerfil(Request $request, Response $response, array $params): void
    {
        $user = $request->getAttribute('authenticated_user');
        if (!$user) {
            $response->error('TOKEN_INVALID', 'Token requerido', 401);
            return;
        }

        try {
            $data = $request->getBody();
            $perfil = $this->service->actualizarPerfil((int)$user['id'], $data);
            $response->json($perfil);
        } catch (\Exception $e) {
            $response->error('SERVER_ERROR', 'Error al actualizar perfil.', 500);
        }
    }

    /**
     * POST /api/auth/logout
     * Cierra sesión
     */
    public function logout(Request $request, Response $response, array $params): void
    {
        Session::destruir();
        $response->json(['mensaje' => 'Sesión cerrada exitosamente.']);
    }
}
