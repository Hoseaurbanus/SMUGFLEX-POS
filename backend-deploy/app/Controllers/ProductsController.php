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

        $where = "p.is_deleted = 0";
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
        if (isset($params['is_active']) && $params['is_active'] !== '') {
            $where .= " AND p.is_active = ?";
            $queryParams[] = (int)$params['is_active'];
        }

        $total = $db->fetch("SELECT COUNT(*) as cnt FROM products p WHERE $where", $queryParams)['cnt'];

        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name, u.name as unit_name
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN brands b ON b.id = p.brand_id
                LEFT JOIN units u ON u.id = p.unit_id
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
            $exists = $db->fetch("SELECT id FROM products WHERE sku = ?", [$body['sku']]);
            if ($exists) {
                Response::error('SKU already exists', 409);
            }
        }

        $productId = $db->insert('products', [
            'name' => $body['name'],
            'sku' => $body['sku'] ?? null,
            'barcode' => $body['barcode'] ?? null,
            'description' => $body['description'] ?? null,
            'category_id' => $body['category_id'] ?? null,
            'brand_id' => $body['brand_id'] ?? null,
            'unit_id' => $body['unit_id'] ?? null,
            'cost_price' => $body['cost_price'] ?? 0,
            'selling_price' => $body['selling_price'] ?? 0,
            'stock_quantity' => $body['stock_quantity'] ?? 0,
            'minimum_stock' => $body['minimum_stock'] ?? 0,
            'warehouse_id' => $body['warehouse_id'] ?? null,
            'is_active' => $body['is_active'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $product = $db->fetch("SELECT * FROM products WHERE id = ?", [$productId]);
        Response::success($product, 'Product created', 201);
    }

    public function show(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $product = $db->fetch(
            "SELECT p.*, c.name as category_name, b.name as brand_name, u.name as unit_name, w.name as warehouse_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN brands b ON b.id = p.brand_id
             LEFT JOIN units u ON u.id = p.unit_id
             LEFT JOIN warehouses w ON w.id = p.warehouse_id
             WHERE p.id = ? AND p.is_deleted = 0",
            [$id]
        );

        if (!$product) {
            Response::error('Product not found', 404);
        }

        Response::success($product);
    }

    public function update(string $id): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();
        $db = Database::getInstance();

        $product = $db->fetch("SELECT id FROM products WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$product) {
            Response::error('Product not found', 404);
        }

        if (!empty($body['sku'])) {
            $exists = $db->fetch("SELECT id FROM products WHERE sku = ? AND id != ?", [$body['sku'], $id]);
            if ($exists) {
                Response::error('SKU already exists', 409);
            }
        }

        $updateData = [];
        $fields = ['name', 'sku', 'barcode', 'description', 'category_id', 'brand_id', 'unit_id',
                    'cost_price', 'selling_price', 'stock_quantity', 'minimum_stock', 'warehouse_id', 'is_active'];
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

        $product = $db->fetch("SELECT * FROM products WHERE id = ?", [$id]);
        Response::success($product, 'Product updated');
    }

    public function delete(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $product = $db->fetch("SELECT id FROM products WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$product) {
            Response::error('Product not found', 404);
        }

        $db->update('products', ['is_deleted' => 1, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        Response::success(null, 'Product deleted');
    }

    public function stockHistory(string $id): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();
        $params = Request::getQueryParams();

        $product = $db->fetch("SELECT id FROM products WHERE id = ? AND is_deleted = 0", [$id]);
        if (!$product) {
            Response::error('Product not found', 404);
        }

        $history = $db->fetchAll(
            "SELECT * FROM stock_movements WHERE product_id = ? ORDER BY created_at DESC",
            [$id]
        );

        Response::success($history);
    }

    public function barcodeLookup(string $barcode): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $product = $db->fetch(
            "SELECT p.*, c.name as category_name, b.name as brand_name, u.name as unit_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN brands b ON b.id = p.brand_id
             LEFT JOIN units u ON u.id = p.unit_id
             WHERE p.barcode = ? AND p.is_deleted = 0",
            [$barcode]
        );

        if (!$product) {
            Response::error('Product not found', 404);
        }

        Response::success($product);
    }
}
