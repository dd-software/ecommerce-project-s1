<?php
declare(strict_types=1);

namespace Src\Models;

use Config\Database;
use PDO;
use Exception;

class Order {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Convierte el carrito actual en una nueva orden y toma snapshot del precio.
     */
    public function createFromCart(int $userId): ?int {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                SELECT c.variant_id, c.quantity, v.price, COALESCE(i.stock, 0) as stock
                FROM cart_items c
                JOIN product_variants v ON c.variant_id = v.id
                LEFT JOIN inventory i ON v.id = i.variant_id
                WHERE c.user_id = :user_id
            ");
            $stmt->execute(['user_id' => $userId]);
            $items = $stmt->fetchAll();

            if (empty($items)) {
                throw new Exception("El carrito está vacío. No se puede proceder.");
            }

            $totalAmount = 0;
            foreach ($items as $item) {
                if ($item['quantity'] > $item['stock']) {
                    throw new Exception("Stock insuficiente para el producto (Variant ID: {$item['variant_id']}).");
                }
                $totalAmount += ($item['quantity'] * $item['price']);
            }

            // Crear la Orden
            $stmtOrder = $this->db->prepare("
                INSERT INTO orders (user_id, total_amount, status) 
                VALUES (:user_id, :total_amount, 'pendiente_pago')
            ");
            $stmtOrder->execute([
                'user_id' => $userId,
                'total_amount' => $totalAmount
            ]);
            
            $orderId = (int)$this->db->lastInsertId();

            // Snapshot inmutable de los precios en Detalles de la Orden
            $stmtDetail = $this->db->prepare("
                INSERT INTO order_items (order_id, variant_id, quantity, unit_price)
                VALUES (:order_id, :variant_id, :quantity, :unit_price)
            ");

            foreach ($items as $item) {
                $stmtDetail->execute([
                    'order_id' => $orderId,
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price']
                ]);
            }

            $this->db->commit();
            return $orderId;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * RN-003: El stock se descuenta solo tras estado 'pagado'.
     */
    public function markAsPaidAndDeductStock(int $orderId): void {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT user_id, status FROM orders WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                throw new Exception("Orden no encontrada.");
            }

            if ($order['status'] !== 'pendiente_pago') {
                throw new Exception("La orden ya fue pagada o su estado no permite pagos.");
            }

            $stmtUpdate = $this->db->prepare("UPDATE orders SET status = 'pagado' WHERE id = :id");
            $stmtUpdate->execute(['id' => $orderId]);

            $stmtItems = $this->db->prepare("SELECT variant_id, quantity FROM order_items WHERE order_id = :id");
            $stmtItems->execute(['id' => $orderId]);
            $items = $stmtItems->fetchAll();

            $stmtStock = $this->db->prepare("
                UPDATE inventory SET stock = stock - :qty_deduct 
                WHERE variant_id = :variant_id AND stock >= :qty_check
            ");

            foreach ($items as $item) {
                $stmtStock->execute([
                    'qty_deduct' => $item['quantity'],
                    'variant_id' => $item['variant_id'],
                    'qty_check' => $item['quantity']
                ]);
                if ($stmtStock->rowCount() === 0) {
                    throw new Exception("Inconsistencia crítica: Stock insuficiente al momento del cobro para variante: {$item['variant_id']}.");
                }
            }

            // Vaciar el carrito ahora que el pago fue realmente exitoso
            $stmtClear = $this->db->prepare("DELETE FROM cart_items WHERE user_id = :user_id");
            $stmtClear->execute(['user_id' => $order['user_id']]);

            $this->db->commit();

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * RN-005: Trazabilidad obligatoria de estados.
     */
    public function updateStatus(int $orderId, string $status): void {
        $validStatuses = ['pendiente_pago', 'pagado', 'en_preparacion', 'enviado', 'entregado'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception("Estado proporcionado '{$status}' no es válido.");
        }
        $stmtUpdate = $this->db->prepare("UPDATE orders SET status = :status WHERE id = :id");
        $stmtUpdate->execute(['status' => $status, 'id' => $orderId]);
    }

    public function getAll(): array {
        $stmt = $this->db->query("
            SELECT o.id, o.total_amount, o.status, o.created_at, u.email as customer_email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Devuelve el historial de compras de un usuario específico junto con sus ítems.
     */
    public function getByUserId(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT id, total_amount, status, created_at
            FROM orders
            WHERE user_id = :user_id
            ORDER BY created_at DESC
        ");
        $stmt->execute(['user_id' => $userId]);
        $orders = $stmt->fetchAll();
        
        foreach ($orders as &$order) {
            $stmtItems = $this->db->prepare("
                SELECT oi.quantity, oi.unit_price, p.name as product_name, pv.sku
                FROM order_items oi
                JOIN product_variants pv ON oi.variant_id = pv.id
                JOIN products p ON pv.product_id = p.id
                WHERE oi.order_id = :order_id
            ");
            $stmtItems->execute(['order_id' => $order['id']]);
            $order['items'] = $stmtItems->fetchAll();
        }
        
        return $orders;
    }
}
