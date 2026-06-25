<?php
declare(strict_types=1);

namespace Src\Models;

use Config\Database;
use PDO;

class Cart {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getItems(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT c.variant_id, c.quantity, 
                   v.sku, v.price, p.name as product_name, COALESCE(i.stock, 0) as available_stock
            FROM cart_items c
            JOIN product_variants v ON c.variant_id = v.id
            JOIN products p ON v.product_id = p.id
            LEFT JOIN inventory i ON v.id = i.variant_id
            WHERE c.user_id = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function addItem(int $userId, int $variantId, int $quantity): bool {
        // Inserta o suma la cantidad si ya existe (UPSERT logic).
        $stmt = $this->db->prepare("
            INSERT INTO cart_items (user_id, variant_id, quantity) 
            VALUES (:user_id, :variant_id, :quantity)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
        ");
        return $stmt->execute([
            'user_id' => $userId,
            'variant_id' => $variantId,
            'quantity' => $quantity
        ]);
    }

    public function removeItem(int $userId, int $variantId): bool {
        $stmt = $this->db->prepare("DELETE FROM cart_items WHERE user_id = :user_id AND variant_id = :variant_id");
        return $stmt->execute([
            'user_id' => $userId,
            'variant_id' => $variantId
        ]);
    }

    public function clear(int $userId): bool {
        $stmt = $this->db->prepare("DELETE FROM cart_items WHERE user_id = :user_id");
        return $stmt->execute(['user_id' => $userId]);
    }
}
