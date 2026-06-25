<?php
declare(strict_types=1);

namespace Src\Models;

use Config\Database;
use PDO;

class Inventory {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Obtiene el listado de variantes con stock por debajo o igual a su umbral.
     */
    public function getAlerts(): array {
        $stmt = $this->db->query("
            SELECT i.id as inventory_id, v.sku, i.stock, i.min_stock_alert, p.name as product_name
            FROM inventory i
            JOIN product_variants v ON i.variant_id = v.id
            JOIN products p ON v.product_id = p.id
            WHERE i.stock <= i.min_stock_alert
        ");
        return $stmt->fetchAll();
    }
}
