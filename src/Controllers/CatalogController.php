<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Core\Response;
use Src\Models\Product;

class CatalogController {
    private Product $productModel;

    public function __construct() {
        $this->productModel = new Product();
    }

    /** GET /api/catalog/products[?category_id={id}] */
    public function list(): void {
        $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        $products = $categoryId
            ? $this->productModel->getByCategory($categoryId)
            : $this->productModel->getAll();
        Response::json(['data' => $products]);
    }

    /** GET /api/catalog/products/search?q={term} */
    public function search(): void {
        $query = trim($_GET['q'] ?? '');
        if (mb_strlen($query) < 2) {
            Response::json(['data' => []]);
            return;
        }
        $products = $this->productModel->search($query);
        Response::json(['data' => $products]);
    }

    /** GET /api/catalog/categories */
    public function categories(): void {
        $categories = $this->productModel->getCategories();
        Response::json(['data' => $categories]);
    }

    /** GET /api/catalog/products/{id} */
    public function detail(array $params): void {
        $id = (int)($params['id'] ?? 0);
        $product = $this->productModel->getWithVariants($id);
        if (!$product) {
            Response::error('Producto no encontrado', 404);
        }
        Response::json(['data' => $product]);
    }
}
