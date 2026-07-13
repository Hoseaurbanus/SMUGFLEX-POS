<?php

namespace App\Controllers;

use Database;
use Request;
use Response;
use AuthMiddleware;

class ProductsController
{
    public function index(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $params = Request::getQueryParams();
        $page = max(1, (int)($params['page'] ?? 1));
        $perPage = max(1, min(100, (int)($params['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $where = "p.deleted_at IS NULL";
        $queryParams = [];

        if (!empty($params['search'])) {
            $search = '%' . $params['search'] . '%';
            $where .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)";
            $queryParams = array_merge($queryParams, [$search, $search, $search]);
        }
        if (!empty($params['category_id'])) {
            $where .= " AND p.category_id = ?";
            $queryParams[] = $params['category_id'];
        }
        if (!empty($params['brand_id'])) {
            $where .= " AND p.brand_id = ?";
            $queryParams[] = $params['brand_id'];
        }
        if (!empty($params['status'])) {
            $where .= " AND p.status = ?";
            $queryParams[] = $params['status'];
        }

        $total = $db->fetch("SELECT COUNT(*) as cnt FROM products p WHERE $where", $queryParams)['cnt'];

        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name, un.name as unit_name,
                       (SELECT COALESCE(SUM(ps.quantity), 0) FROM product_stocks ps WHERE ps.product_id = p.id) as stock_quantity
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN brands b ON b.id = p.brand_id
                LEFT JOIN units un ON un.id = p.unit_id
                WHERE $where
                ORDER BY p.created_at DESC
                LIMIT $perPage OFFSET $offset";

        $products = $db->fetchAll($sql, $queryParams);
        Response::paginated($products, (int)$total, $page, $perPage);
    }

    public function create(): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();

        if (!$body || empty($body['name'])) {
            Response::error('Product name is required', 422);
        }

        $db = Database::getInstance();

        if (!empty($body['sku'])) {
            $exists = $db->fetch("SELECT id FROM products WHERE sku = ? AND deleted_at IS NULL", [$body['sku']]);
            if ($exists) {
                Response::error('SKU already exists', 409);
            }
        }

        $productId = $db->insert('products', [
            'name' => $body['name'],
            'slug' => $body['slug'] ?? strtolower(str_replace(' ', '-', $body['name'])),
            'sku' => $body['sku'] ?? null,
            'barcode' => $body['barcode'] ?? null,
            'description' => $body['description'] ?? null,
            'category_id' => $body['category_id'] ?? null,
            'brand_id' => $body['brand_id'] ?? null,
            'unit_id' => $body['unit_id'] ?? null,
            'buying_price' => $body['buying_price'] ?? 0,
            'selling_price' => $body['selling_price'] ?? 0,
            'wholesale_price' => $body['wholesale_price'] ?? 0,
            'minimum_price' => $body['minimum_price'] ?? 0,
            'tax_rate' => $body['tax_rate'] ?? 0,
            'discount_rate' => $body['discount_rate'] ?? 0,
            'minimum_stock' => $body['minimum_stock'] ?? 0,
            'has_variants' => $body['has_variants'] ?? 0,
            'has_serial' => $body['has_serial'] ?? 0,
            'has_expiry' => $body['has_expiry'] ?? 0,
            'image' => $body['image'] ?? null,
            'status' => $body['status'] ?? 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $product = $db->fetch(
            "SELECT p.*, (SELECT COALESCE(SUM(ps.quantity), 0) FROM product_stocks ps WHERE ps.product_id = p.id) as stock_quantity
             FROM products p WHERE p.id = ?",
            [$productId]
        );
        Response::success($product, 'Product created', 201);
    }

    public function show(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $product = $db->fetch(
            "SELECT p.*, c.name as category_name, b.name as brand_name, un.name as unit_name,
                    (SELECT COALESCE(SUM(ps.quantity), 0) FROM product_stocks ps WHERE ps.product_id = p.id) as stock_quantity
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN brands b ON b.id = p.brand_id
             LEFT JOIN units un ON un.id = p.unit_id
             WHERE p.id = ? AND p.deleted_at IS NULL",
            [$id]
        );

        if (!$product) {
            Response::error('Product not found', 404);
        }

        $stocks = $db->fetchAll(
            "SELECT ps.*, w.name as warehouse_name
             FROM product_stocks ps
             LEFT JOIN warehouses w ON w.id = ps.warehouse_id
             WHERE ps.product_id = ?",
            [$id]
        );
        $product['stocks'] = $stocks;

        Response::success($product);
    }

    public function update(string $id): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();
        $db = Database::getInstance();

        $product = $db->fetch("SELECT id FROM products WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$product) {
            Response::error('Product not found', 404);
        }

        if (!empty($body['sku'])) {
            $exists = $db->fetch("SELECT id FROM products WHERE sku = ? AND id != ? AND deleted_at IS NULL", [$body['sku'], $id]);
            if ($exists) {
                Response::error('SKU already exists', 409);
            }
        }

        $updateData = [];
        $fields = ['name', 'slug', 'sku', 'barcode', 'description', 'category_id', 'brand_id', 'unit_id',
                    'buying_price', 'selling_price', 'wholesale_price', 'minimum_price', 'tax_rate', 'discount_rate',
                    'minimum_stock', 'has_variants', 'has_serial', 'has_expiry', 'image', 'status'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $body)) {
                $updateData[$field] = $body[$field];
            }
        }

        if (empty($updateData)) {
            Response::error('No data to update', 422);
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');
        $db->update('products', $updateData, 'id = ?', [$id]);

        $product = $db->fetch(
            "SELECT p.*, (SELECT COALESCE(SUM(ps.quantity), 0) FROM product_stocks ps WHERE ps.product_id = p.id) as stock_quantity
             FROM products p WHERE p.id = ?",
            [$id]
        );
        Response::success($product, 'Product updated');
    }

    public function delete(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $product = $db->fetch("SELECT id FROM products WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$product) {
            Response::error('Product not found', 404);
        }

        $db->update('products', ['deleted_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        Response::success(null, 'Product deleted');
    }

    public function stockHistory(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $params = Request::getQueryParams();

        $product = $db->fetch("SELECT id FROM products WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$product) {
            Response::error('Product not found', 404);
        }

        $history = $db->fetchAll(
            "SELECT sm.*, w.name as warehouse_name, CONCAT(u.first_name, ' ', u.last_name) as user_name
             FROM stock_movements sm
             LEFT JOIN warehouses w ON w.id = sm.warehouse_id
             LEFT JOIN users u ON u.id = sm.user_id
             WHERE sm.product_id = ? ORDER BY sm.created_at DESC",
            [$id]
        );

        Response::success($history);
    }

    public function barcodeLookup(string $barcode): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $product = $db->fetch(
            "SELECT p.*, c.name as category_name, b.name as brand_name, un.name as unit_name,
                    (SELECT COALESCE(SUM(ps.quantity), 0) FROM product_stocks ps WHERE ps.product_id = p.id) as stock_quantity
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN brands b ON b.id = p.brand_id
             LEFT JOIN units un ON un.id = p.unit_id
             WHERE p.barcode = ? AND p.deleted_at IS NULL",
            [$barcode]
        );

        if (!$product) {
            Response::error('Product not found', 404);
        }

        Response::success($product);
    }
}
