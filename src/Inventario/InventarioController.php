<?php

declare(strict_types=1);

/**
 * InventarioController — Gestión de stock y movimientos de inventario.
 *
 * CAMBIOS ARQUITECTÓNICOS RESPECTO A LA VERSIÓN ANTERIOR:
 *
 * 1. DEPENDENCY INJECTION con fallback: el constructor acepta un
 *    InventarioService inyectado (ideal para tests con mocks). Si no se
 *    pasa nada, construye la cadena de dependencias real por defecto, para
 *    no romper código existente que haga `new InventarioController()`.
 *
 * 2. MÉTODO requireRole() extraído: la lógica de autorización RBAC estaba
 *    duplicada literalmente en tres métodos. Ahora vive en un único lugar.
 *    Si cambian los roles o la lógica, se modifica en un solo sitio.
 *
 * 3. CONSTANTE ALLOWED_ROLES: los roles autorizados ya no están hardcodeados
 *    en cada if. Quedan documentados y son fáciles de auditar.
 *
 * 4. in_array() con tercer argumento true: comparación estricta de tipos,
 *    obligatoria según la spec de seguridad (php.md §10).
 *
 * 5. Tipos de retorno completos en todos los métodos (void explícito).
 */

namespace App\Inventario;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\AppException;

class InventarioController
{
    /**
     * Roles autorizados para operaciones administrativas de inventario.
     * Actualizar aquí si se agregan nuevos roles en arquitectura-general.md §5.1
     */
    private const ALLOWED_ROLES = ['admin', 'supervisor', 'vendedor'];

    private readonly InventarioService $service;

    /**
     * El Service puede inyectarse explícitamente (recomendado para tests
     * unitarios, donde se pasa un mock/stub):
     *
     *   $controller = new InventarioController($mockService);
     *
     * Si no se pasa nada — por ejemplo, si el router actual hace
     * `new InventarioController()` — se construye la cadena de
     * dependencias real por defecto (Repository + Service con la
     * conexión PDO de la aplicación).
     */
    public function __construct(?InventarioService $service = null)
    {
        $this->service = $service ?? new InventarioService(
            new InventarioRepository(Database::getInstance()->getConnection())
        );
    }

    /**
     * GET /api/v1/inventory
     * Verifica disponibilidad de stock para uno o más productos.
     * Ruta pública — no requiere autenticación.
     *
     * Query params:
     *   product_ids (string): IDs separados por coma. Ej: "1,2,42"
     */
    public function verificar(Request $request, Response $response, array $params): void
    {
        $rawIds = $request->getQuery('product_ids', '');

        if (empty($rawIds)) {
            $response->error('VALIDATION_ERROR', 'El parámetro product_ids es requerido.', 400);
            return;
        }

        // Convertir a enteros y filtrar valores inválidos (0, negativos)
        $ids = array_values(
            array_filter(
                array_map('intval', explode(',', $rawIds)),
                fn(int $id): bool => $id > 0
            )
        );

        if (empty($ids)) {
            $response->error('VALIDATION_ERROR', 'Debe especificar al menos un product_id válido.', 400);
            return;
        }

        try {
            $disponibilidad = $this->service->verificarDisponibilidad($ids);
            $response->json(['success' => true, 'data' => $disponibilidad]);
        } catch (\Throwable $e) {
            $response->error('SERVER_ERROR', 'Error al verificar inventario.', 500);
        }
    }

    /**
     * GET /api/v1/inventory/movements
     * Lista movimientos de inventario con paginación.
     * Requiere rol: admin, supervisor o vendedor.
     *
     * Query params:
     *   product_id (int, opcional): filtrar por producto
     *   page       (int, default 1)
     *   per_page   (int, default 20, máx 100)
     */
    public function movimientos(Request $request, Response $response, array $params): void
    {
        if (!$this->requireRole($request, $response)) {
            return;
        }

        try {
            $productoId = $request->getQuery('product_id');
            $pagina     = max(1, (int) ($request->getQuery('page', '1')));
            $porPagina  = min(100, max(1, (int) ($request->getQuery('per_page', '20'))));

            $resultado = $this->service->listarMovimientos(
                productoId: $productoId !== null ? (int) $productoId : null,
                pagina: $pagina,
                porPagina: $porPagina
            );

            $response->paginated(
                $resultado['movimientos'],
                $resultado['total'],
                $pagina,
                $porPagina
            );
        } catch (\Throwable $e) {
            $response->error('SERVER_ERROR', 'Error al listar movimientos.', 500);
        }
    }

    /**
     * GET /api/v1/inventory/alerts
     * Productos con stock igual o por debajo del stock mínimo configurado.
     * Requiere rol: admin, supervisor o vendedor.
     */
    public function alertas(Request $request, Response $response, array $params): void
    {
        if (!$this->requireRole($request, $response)) {
            return;
        }

        try {
            $alertas = $this->service->obtenerAlertasStock();
            $response->json(['success' => true, 'data' => $alertas]);
        } catch (\Throwable $e) {
            $response->error('SERVER_ERROR', 'Error al obtener alertas de stock.', 500);
        }
    }

    /**
     * POST /api/v1/inventory/adjust
     * Ajusta el stock de un producto manualmente.
     * Requiere rol: admin, supervisor o vendedor.
     *
     * Body JSON:
     *   product_id (int)    : ID del producto
     *   quantity   (int)    : Cantidad a ingresar (entrada) o nuevo valor absoluto (ajuste)
     *   tipo       (string) : "entrada" | "ajuste"
     *   motivo     (string) : Descripción del motivo del movimiento
     */
    public function ajustar(Request $request, Response $response, array $params): void
    {
        if (!$this->requireRole($request, $response)) {
            return;
        }

        try {
            $data = $request->getBody();

            // Validar campos requeridos antes de cualquier lógica de negocio
            $camposFaltantes = $this->validarCamposRequeridos(
                $data,
                ['product_id', 'quantity', 'tipo', 'motivo']
            );

            if (!empty($camposFaltantes)) {
                $response->error(
                    'VALIDATION_ERROR',
                    'Campos requeridos faltantes: ' . implode(', ', $camposFaltantes),
                    400
                );
                return;
            }

            // Validar tipos y rangos
            $productoId = filter_var($data['product_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $cantidad   = filter_var($data['quantity'],    FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

            if ($productoId === false) {
                $response->error('VALIDATION_ERROR', 'product_id debe ser un entero positivo.', 400);
                return;
            }

            if ($cantidad === false) {
                $response->error('VALIDATION_ERROR', 'quantity debe ser un entero no negativo.', 400);
                return;
            }

            $tipo   = trim((string) $data['tipo']);
            $motivo = trim((string) $data['motivo']);

            if (strlen($motivo) < 3 || strlen($motivo) > 500) {
                $response->error('VALIDATION_ERROR', 'El motivo debe tener entre 3 y 500 caracteres.', 400);
                return;
            }

            // Obtener usuario autenticado (inyectado por JwtMiddleware)
            $user = $request->getAttribute('authenticated_user');

            $this->service->ajustarStock(
                productoId: $productoId,
                cantidad: $cantidad,
                tipo: $tipo,
                motivo: $motivo,
                userId: (int) $user['id']
            );

            $response->json(['success' => true, 'message' => 'Stock ajustado exitosamente.']);

        } catch (AppException $e) {
            // BusinessException (409), NotFoundException (404), etc.
            // Cada subclase ya sabe su propio código HTTP y código de error.
            $response->error($e->getErrorCode(), $e->getMessage(), $e->getHttpStatus());
        } catch (\InvalidArgumentException $e) {
            $response->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\Throwable $e) {
            $response->error('SERVER_ERROR', 'Error al ajustar stock.', 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Métodos privados de soporte
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Verifica que el usuario autenticado tenga uno de los roles permitidos.
     * Retorna true si está autorizado, false si ya se envió una respuesta 403.
     *
     * Centralizar aquí evita duplicar la lógica de autorización en cada método.
     */
    private function requireRole(Request $request, Response $response): bool
    {
        $user = $request->getAttribute('authenticated_user');

        // El middleware JWT garantiza que $user existe en rutas protegidas,
        // pero la verificación defensiva protege ante configuraciones erróneas.
        if ($user === null) {
            $response->error('UNAUTHORIZED', 'Autenticación requerida.', 401);
            return false;
        }

        // Tercer argumento true = comparación estricta (tipo y valor)
        if (!in_array($user['role'], self::ALLOWED_ROLES, true)) {
            $response->error('INSUFFICIENT_PERMISSIONS', 'No tienes permisos para esta operación.', 403);
            return false;
        }

        return true;
    }

    /**
     * Verifica que todos los campos requeridos existan y no estén vacíos en $data.
     * Retorna la lista de campos faltantes (vacía si todo está bien).
     *
     * @param  array<string, mixed>  $data
     * @param  string[]              $campos
     * @return string[]
     */
    private function validarCamposRequeridos(array $data, array $campos): array
    {
        $faltantes = [];

        foreach ($campos as $campo) {
            if (!isset($data[$campo]) || $data[$campo] === '' || $data[$campo] === null) {
                $faltantes[] = $campo;
            }
        }

        return $faltantes;
    }
}