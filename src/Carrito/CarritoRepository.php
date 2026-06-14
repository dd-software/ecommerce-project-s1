<?php

declare(strict_types=1);

/**
 * CarritoRepository - Acceso a datos del carrito de compras
 */
namespace App\Carrito;

use App\Core\Database;
use PDO;

class CarritoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Crea un nuevo carrito
     */
    public function crearCarrito(?int $userId, ?string $sessionId): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO carritos (id_usuario, session_id, activo) VALUES (:usuario, :session, 1)"
        );
        $stmt->execute([
            ':usuario' => $userId,
            ':session' => $sessionId,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Obtiene el carrito activo de un usuario
     */
    public function obtenerCarritoActivoPorUsuario(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, id_usuario, session_id, activo, created_at
             FROM carritos
             WHERE id_usuario = :uid AND activo = 1
             ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Obtiene el carrito activo de una sesión de visitante
     */
    public function obtenerCarritoActivoPorSession(string $sessionId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, id_usuario, session_id, activo, created_at
             FROM carritos
             WHERE session_id = :sid AND id_usuario IS NULL AND activo = 1
             ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([':sid' => $sessionId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Obtiene los items de un carrito con datos del producto
     */
    public function obtenerItems(int $carritoId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ic.id, ic.id_carrito, ic.id_producto, ic.cantidad, ic.precio_unitario,
                    p.nombre, p.imagen_url, p.stock, p.slug, p.id_categoria
             FROM items_carrito ic
             INNER JOIN productos p ON ic.id_producto = p.id AND p.activo = 1 AND p.deleted_at IS NULL
             WHERE ic.id_carrito = :carrito_id"
        );
        $stmt->execute([':carrito_id' => $carritoId]);
        return $stmt->fetchAll();
    }

    /**
     * Busca un item existente en el carrito
     */
    public function buscarItem(int $carritoId, int $productoId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, id_carrito, id_producto, cantidad, precio_unitario
             FROM items_carrito
             WHERE id_carrito = :carrito_id AND id_producto = :producto_id"
        );
        $stmt->execute([
            ':carrito_id'  => $carritoId,
            ':producto_id' => $productoId,
        ]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Busca un item por su ID
     */
    public function buscarItemPorId(int $itemId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, id_carrito, id_producto, cantidad, precio_unitario
             FROM items_carrito WHERE id = :id"
        );
        $stmt->execute([':id' => $itemId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Agrega un nuevo item al carrito
     */
    public function agregarItem(int $carritoId, int $productoId, int $cantidad, int $precioUnitario): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO items_carrito (id_carrito, id_producto, cantidad, precio_unitario)
             VALUES (:carrito_id, :producto_id, :cantidad, :precio)"
        );
        $stmt->execute([
            ':carrito_id'  => $carritoId,
            ':producto_id' => $productoId,
            ':cantidad'    => $cantidad,
            ':precio'      => $precioUnitario,
        ]);
    }

    /**
     * Actualiza la cantidad de un item
     */
    public function actualizarItem(int $itemId, int $cantidad): void
    {
        $stmt = $this->db->prepare(
            "UPDATE items_carrito SET cantidad = :cantidad, updated_at = NOW() WHERE id = :id"
        );
        $stmt->execute([':cantidad' => $cantidad, ':id' => $itemId]);
    }

    /**
     * Elimina un item del carrito
     */
    public function eliminarItem(int $itemId): void
    {
        $stmt = $this->db->prepare("DELETE FROM items_carrito WHERE id = :id");
        $stmt->execute([':id' => $itemId]);
    }

    /**
     * Elimina todos los items de un carrito
     */
    public function vaciarCarrito(int $carritoId): void
    {
        $stmt = $this->db->prepare("DELETE FROM items_carrito WHERE id_carrito = :carrito_id");
        $stmt->execute([':carrito_id' => $carritoId]);
    }

    /**
     * Asigna un carrito de visitante a un usuario (sincronización login)
     */
    public function asignarCarritoAUsuario(int $carritoId, int $userId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE carritos SET id_usuario = :uid, session_id = NULL WHERE id = :id"
        );
        $stmt->execute([':uid' => $userId, ':id' => $carritoId]);
    }

    /**
     * Desactiva un carrito (cuando se hace merge)
     */
    public function desactivarCarrito(int $carritoId): void
    {
        $stmt = $this->db->prepare("UPDATE carritos SET activo = 0 WHERE id = :id");
        $stmt->execute([':id' => $carritoId]);
    }

    /**
     * Desactiva el carrito cuando se convierte en pedido
     */
    public function desactivarParaCheckout(int $carritoId): void
    {
        $stmt = $this->db->prepare("UPDATE carritos SET activo = 0 WHERE id = :id");
        $stmt->execute([':id' => $carritoId]);
    }
}
