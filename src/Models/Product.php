<?php
declare(strict_types=1);

namespace Src\Models;

use Config\Database;
use PDO;

class Product {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAll(): array {
        $stmt = $this->db->query("
            SELECT p.id, p.name, p.slug, p.description, c.name AS category_name
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.deleted_at IS NULL
            ORDER BY p.name
        ");
        return $stmt->fetchAll();
    }

    public function getByCategory(int $categoryId): array {
        $stmt = $this->db->prepare("
            SELECT p.id, p.name, p.slug, p.description, c.name AS category_name
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.deleted_at IS NULL AND p.category_id = ?
            ORDER BY p.name
        ");
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }

    public function search(string $query): array {
        $q = '%' . $query . '%';
        $stmt = $this->db->prepare("
            SELECT p.id, p.name, p.slug, p.description, c.name AS category_name
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.deleted_at IS NULL
              AND (p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)
            ORDER BY p.name
        ");
        $stmt->execute([$q, $q, $q]);
        return $stmt->fetchAll();
    }

    public function getCategories(): array {
        $stmt = $this->db->query("
            SELECT id, name, slug, description
            FROM categories
            WHERE deleted_at IS NULL
            ORDER BY name
        ");
        return $stmt->fetchAll();
    }

    public function getWithVariants(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT p.id, p.name, p.slug, p.description, c.name AS category_name
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.id = :id AND p.deleted_at IS NULL
        ");
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch();

        if (!$product) return null;

        $stmtVars = $this->db->prepare("
            SELECT v.id, v.sku, v.attributes, v.price, COALESCE(i.stock, 0) AS stock
            FROM product_variants v
            LEFT JOIN inventory i ON v.id = i.variant_id
            WHERE v.product_id = :id AND v.deleted_at IS NULL
        ");
        $stmtVars->execute(['id' => $id]);
        $variants = $stmtVars->fetchAll();

        foreach ($variants as &$var) {
            $var['attributes'] = json_decode($var['attributes'], true);
        }

        $product['variants'] = $variants;
        return $product;
    }
}
